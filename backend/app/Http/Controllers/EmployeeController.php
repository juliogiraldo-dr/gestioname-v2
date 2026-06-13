<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Employee\ImportEmployeeRequest;
use App\Http\Requests\Employee\InviteEmployeeRequest;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\BradfordIndexCalculator;
use App\Services\EmployeeImportService;
use App\Services\EmployeeIncidenceService;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeService $service,
        private readonly EmployeeIncidenceService $incidences,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $employees = Employee::query()
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->string('company_id')))
            ->when($request->filled('work_center_id'), fn ($q) => $q->where('work_center_id', $request->string('work_center_id')))
            ->when($request->filled('department'), fn ($q) => $q->where('department', $request->string('department')))
            ->when($request->filled('status'), fn ($q) => $q->where('employment_status', $request->string('status')))
            ->when($request->filled('agreement_id'), fn ($q) => $q->where('agreement_id', $request->string('agreement_id')))
            ->when($request->filled('calendar_id'), fn ($q) => $q->whereHas('calendars', fn ($c) => $c->where('work_calendars.id', $request->string('calendar_id'))))
            ->when($request->has('active'), fn ($q) => $q->where('active', $request->boolean('active')))
            ->with(['workCenter', 'agreement'])
            ->orderBy('last_name')
            ->paginate(20);

        return EmployeeResource::collection($employees);
    }

    /** Contratos activos que vencen en los próximos N días (por defecto 30). Para el aviso del listado. */
    public function expiringContracts(Request $request): JsonResponse
    {
        $days = $request->integer('days') ?: 30;
        $limit = now()->startOfDay()->addDays($days)->toDateString();

        $employees = Employee::query()
            ->where('active', true)
            ->whereNotNull('contract_end_date')
            ->whereDate('contract_end_date', '>=', now()->toDateString())
            ->whereDate('contract_end_date', '<=', $limit)
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->string('company_id')))
            ->orderBy('contract_end_date')
            ->get(['id', 'first_name', 'last_name', 'second_last_name', 'contract_end_date']);

        return response()->json([
            'data' => [
                'count' => $employees->count(),
                'employees' => $employees->map(fn (Employee $e) => [
                    'id' => $e->id,
                    'full_name' => $e->fullName(),
                    'contract_end_date' => $e->contract_end_date?->toDateString(),
                ])->values(),
            ],
        ]);
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = $this->service->create($request->validated());

        return EmployeeResource::make($employee->load('allowedIps'))->response()->setStatusCode(201);
    }

    public function show(Employee $employee): JsonResponse
    {
        $employee->load(['workCenter', 'agreement', 'allowedIps']);

        return EmployeeResource::make($employee)
            ->additional(['meta' => ['incidences' => $this->incidences->for($employee)]])
            ->response();
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): EmployeeResource
    {
        $employee = $this->service->update($employee, $request->validated());

        return EmployeeResource::make($employee->load('allowedIps'));
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $this->service->delete($employee);

        return response()->json(['message' => 'Empleado eliminado correctamente.']);
    }

    public function activate(Employee $employee): EmployeeResource
    {
        return EmployeeResource::make($this->service->setActive($employee, true));
    }

    public function deactivate(Employee $employee): EmployeeResource
    {
        return EmployeeResource::make($this->service->setActive($employee, false));
    }

    public function bradford(Employee $employee, BradfordIndexCalculator $calculator): JsonResponse
    {
        return response()->json(['data' => $calculator->forEmployee($employee)]);
    }

    public function invite(InviteEmployeeRequest $request, AuthService $auth): JsonResponse
    {
        $data = $request->validated();

        $employee = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['first_name'].' '.$data['last_name'],
                'email' => $data['email'],
            ]);
            $user->assignRole('employee');

            return $this->service->create([
                'company_id' => $data['company_id'],
                'work_center_id' => $data['work_center_id'] ?? null,
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email_personal' => $data['email'],
            ]);
        });

        $auth->sendMagicLink($data['email'], app('tenant')->subdomain);

        return EmployeeResource::make($employee)
            ->additional(['message' => 'Invitación enviada por email.'])
            ->response()
            ->setStatusCode(201);
    }

    public function import(ImportEmployeeRequest $request, EmployeeImportService $import): JsonResponse
    {
        $result = $import->import(
            $request->validated('company_id'),
            $request->file('file')->getRealPath(),
        );

        return response()->json(['data' => $result]);
    }

    public function template(EmployeeImportService $import): StreamedResponse
    {
        $contents = $import->templateContents();

        return response()->streamDownload(
            fn () => print ($contents),
            'plantilla_empleados.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    /** Exporta empleados a Excel (mismos filtros que el listado). */
    public function export(Request $request, EmployeeImportService $import): StreamedResponse
    {
        $employees = Employee::query()
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->string('company_id')))
            ->when($request->filled('department'), fn ($q) => $q->where('department', $request->string('department')))
            ->when($request->has('active'), fn ($q) => $q->where('active', $request->boolean('active')))
            ->orderBy('last_name')->orderBy('first_name')
            ->get();

        $contents = $import->export($employees);

        return response()->streamDownload(
            fn () => print ($contents),
            'empleados.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }
}
