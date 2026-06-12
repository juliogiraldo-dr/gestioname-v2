<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Holiday\StoreHolidayRequest;
use App\Http\Requests\Holiday\UpdateHolidayRequest;
use App\Http\Resources\HolidayResource;
use App\Models\Holiday;
use App\Services\HolidayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HolidayController extends Controller
{
    public function __construct(private readonly HolidayService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $holidays = Holiday::query()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            // Filtro por año: los repetibles aplican a cualquier año; los de fecha fija,
            // solo al año indicado.
            ->when($request->filled('year'), function ($q) use ($request) {
                $year = $request->integer('year');
                $q->where(fn ($sub) => $sub->where('repeatable', true)->orWhereYear('date', $year));
            })
            // Filtro por empresa: festivos con algún centro de esa empresa, o sin centros
            // (nacionales: aplican a todas las empresas).
            ->when($request->filled('company_id'), function ($q) use ($request) {
                $companyId = $request->string('company_id')->toString();
                $q->where(fn ($sub) => $sub
                    ->whereHas('workCenters', fn ($wc) => $wc->where('company_id', $companyId))
                    ->orDoesntHave('workCenters'));
            })
            ->with('workCenters')
            ->orderBy('date')
            ->paginate();

        return HolidayResource::collection($holidays);
    }

    public function store(StoreHolidayRequest $request): JsonResponse
    {
        $holiday = $this->service->create($request->validated());

        return HolidayResource::make($holiday->load('workCenters'))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateHolidayRequest $request, Holiday $holiday): HolidayResource
    {
        $holiday = $this->service->update($holiday, $request->validated());

        return HolidayResource::make($holiday->load('workCenters'));
    }

    public function destroy(Holiday $holiday): JsonResponse
    {
        $this->service->delete($holiday);

        return response()->json(['message' => 'Festivo eliminado correctamente.']);
    }
}
