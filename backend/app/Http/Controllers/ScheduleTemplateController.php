<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleTemplate\StoreScheduleTemplateRequest;
use App\Http\Requests\ScheduleTemplate\UpdateScheduleTemplateRequest;
use App\Http\Resources\ScheduleTemplateResource;
use App\Models\ScheduleTemplate;
use App\Services\ScheduleTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ScheduleTemplateController extends Controller
{
    public function __construct(private readonly ScheduleTemplateService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $templates = ScheduleTemplate::query()
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->string('company_id')))
            ->when($request->filled('year'), fn ($q) => $q->where('year', $request->integer('year')))
            ->with('timeRanges')
            ->orderBy('name')
            ->paginate();

        return ScheduleTemplateResource::collection($templates);
    }

    public function store(StoreScheduleTemplateRequest $request): JsonResponse
    {
        $template = $this->service->create($request->validated());

        return ScheduleTemplateResource::make($template->load('timeRanges'))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ScheduleTemplate $scheduleTemplate): ScheduleTemplateResource
    {
        return ScheduleTemplateResource::make($scheduleTemplate->load('timeRanges'));
    }

    public function update(UpdateScheduleTemplateRequest $request, ScheduleTemplate $scheduleTemplate): ScheduleTemplateResource
    {
        $template = $this->service->update($scheduleTemplate, $request->validated());

        return ScheduleTemplateResource::make($template->load('timeRanges'));
    }

    public function destroy(ScheduleTemplate $scheduleTemplate): JsonResponse
    {
        $this->service->delete($scheduleTemplate);

        return response()->json(['message' => 'Plantilla de horario eliminada correctamente.']);
    }
}
