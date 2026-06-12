<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Calendar\ClearCalendarRequest;
use App\Http\Requests\Calendar\CloneCalendarRequest;
use App\Http\Requests\Calendar\FillManualRequest;
use App\Http\Requests\Calendar\FillQuickRequest;
use App\Http\Requests\Calendar\StoreWorkCalendarRequest;
use App\Http\Requests\Calendar\UpdateWorkCalendarRequest;
use App\Http\Resources\WorkCalendarResource;
use App\Models\WorkCalendar;
use App\Services\WorkCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkCalendarController extends Controller
{
    public function __construct(private readonly WorkCalendarService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $calendars = WorkCalendar::query()
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->string('company_id')))
            ->when($request->filled('year'), fn ($q) => $q->where('year', $request->integer('year')))
            ->withCount('days')
            ->orderByDesc('year')
            ->paginate();

        return WorkCalendarResource::collection($calendars);
    }

    public function store(StoreWorkCalendarRequest $request): JsonResponse
    {
        $calendar = $this->service->create($request->validated());

        return WorkCalendarResource::make($calendar)->response()->setStatusCode(201);
    }

    public function show(WorkCalendar $calendar): WorkCalendarResource
    {
        return WorkCalendarResource::make($calendar->load('days.template'));
    }

    public function update(UpdateWorkCalendarRequest $request, WorkCalendar $calendar): WorkCalendarResource
    {
        return WorkCalendarResource::make($this->service->update($calendar, $request->validated()));
    }

    public function destroy(WorkCalendar $calendar): JsonResponse
    {
        $this->service->delete($calendar);

        return response()->json(['message' => 'Calendario eliminado correctamente.']);
    }

    public function fillQuick(FillQuickRequest $request, WorkCalendar $calendar): JsonResponse
    {
        $filled = $this->service->fillQuick(
            $calendar,
            $request->validated('weekdays'),
            $request->validated('months'),
            $request->validated('schedule_template_id'),
            $request->boolean('include_holidays'),
        );

        return response()->json(['message' => 'Calendario rellenado', 'days_filled' => $filled]);
    }

    public function fillManual(FillManualRequest $request, WorkCalendar $calendar): JsonResponse
    {
        $filled = $this->service->fillManual(
            $calendar,
            $request->validated('dates'),
            $request->validated('schedule_template_id'),
        );

        return response()->json(['message' => 'Calendario rellenado', 'days_filled' => $filled]);
    }

    public function clear(ClearCalendarRequest $request, WorkCalendar $calendar): JsonResponse
    {
        $deleted = $this->service->clear(
            $calendar,
            $request->validated('date_from'),
            $request->validated('date_to'),
            $request->validated('dates'),
        );

        return response()->json(['message' => 'Días borrados', 'days_deleted' => $deleted]);
    }

    public function clone(CloneCalendarRequest $request, WorkCalendar $calendar): JsonResponse
    {
        $clone = $this->service->clone(
            $calendar,
            $request->validated('target_year'),
            $request->validated('name'),
        );

        return WorkCalendarResource::make($clone->loadCount('days'))->response()->setStatusCode(201);
    }

    public function simulateVacation(Request $request, WorkCalendar $calendar): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        return response()->json([
            'data' => $this->service->simulateVacation($calendar, $validated['date_from'], $validated['date_to']),
        ]);
    }

    /** Asigna (sincroniza) los empleados que siguen este calendario. */
    public function assignEmployees(Request $request, WorkCalendar $calendar): JsonResponse
    {
        $validated = $request->validate([
            'employee_ids' => ['present', 'array'],
            'employee_ids.*' => ['uuid', 'exists:employees,id'],
        ]);

        $calendar->employees()->sync($validated['employee_ids']);

        return response()->json([
            'message' => 'Empleados asignados al calendario',
            'employees_count' => count($validated['employee_ids']),
        ]);
    }
}
