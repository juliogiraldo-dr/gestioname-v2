<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Communication;
use App\Models\Entity;
use App\Models\QuotaReminderSetting;
use App\Services\CommunicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Comunicaciones: email masivo a socios y empleados, historial y ajustes del
 * recordatorio automático de cuota por entidad.
 */
class ComunicacionesController extends Controller
{
    public function __construct(private readonly CommunicationService $service) {}

    /** Nº de socios que recibirían el email con los filtros dados (vista previa). */
    public function previewSocios(Request $request, Entity $entity): JsonResponse
    {
        $recipients = $this->service->socios($entity, $this->socioFilters($request));

        return response()->json(['data' => [
            'count' => $recipients->count(),
            'sample' => $recipients->take(5)->map(fn ($m) => trim("{$m->first_name} {$m->last_name}"))->values(),
        ]]);
    }

    /** Envía un email a los socios filtrados de una entidad. */
    public function sendSocios(Request $request, Entity $entity): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string'],
            'status' => ['nullable', 'string'],
            'member_type_id' => ['nullable', 'uuid'],
            'payment' => ['nullable', 'in:pagado,pendiente'],
        ]);

        $filters = $this->socioFilters($request);
        $recipients = $this->service->socios($entity, $filters);
        $count = $this->service->send($recipients->pluck('email'), $data['subject'], $data['body']);

        $this->service->log('socios', $data['subject'], $data['body'], $count, $entity->id, $filters, 'manual', $request->user()?->id);

        return response()->json(['data' => ['sent' => $count]], 201);
    }

    /** Nº de empleados que recibirían el email (vista previa). */
    public function previewEmpleados(Request $request): JsonResponse
    {
        $recipients = $this->service->empleados($request->string('company_id')->value() ?: null);

        return response()->json(['data' => ['count' => $recipients->count()]]);
    }

    /** Envía un email a los empleados (de una empresa o de todas). */
    public function sendEmpleados(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string'],
            'company_id' => ['nullable', 'uuid'],
        ]);

        $recipients = $this->service->empleados($data['company_id'] ?? null);
        $emails = $recipients->map(fn ($e) => $e->contactEmail())->filter()->values();
        $count = $this->service->send($emails, $data['subject'], $data['body']);

        $this->service->log('empleados', $data['subject'], $data['body'], $count, null, ['company_id' => $data['company_id'] ?? null], 'manual', $request->user()?->id);

        return response()->json(['data' => ['sent' => $count]], 201);
    }

    /** Historial de comunicaciones enviadas. */
    public function history(): JsonResponse
    {
        $items = Communication::with(['entity:id,name', 'sender:id,name'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $items->getCollection()->transform(fn (Communication $c) => [
            'id' => $c->id,
            'audience' => $c->audience,
            'entity' => $c->entity?->name,
            'subject' => $c->subject,
            'recipients_count' => $c->recipients_count,
            'trigger' => $c->trigger,
            'sent_by' => $c->sender?->name,
            'created_at' => $c->created_at?->toIso8601String(),
        ]);

        return response()->json($items);
    }

    /** Ajustes del recordatorio automático de cuota de una entidad. */
    public function reminderShow(Entity $entity): JsonResponse
    {
        $settings = QuotaReminderSetting::firstOrCreate(['entity_id' => $entity->id]);

        return response()->json(['data' => $settings]);
    }

    public function reminderUpdate(Request $request, Entity $entity): JsonResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'days_before' => ['required', 'integer', 'between:1,180'],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string'],
        ]);

        $settings = QuotaReminderSetting::updateOrCreate(['entity_id' => $entity->id], $data);

        return response()->json(['data' => $settings]);
    }

    /**
     * @return array{status: ?string, member_type_id: ?string, payment: ?string}
     */
    private function socioFilters(Request $request): array
    {
        return [
            'status' => $request->string('status')->value() ?: null,
            'member_type_id' => $request->string('member_type_id')->value() ?: null,
            'payment' => $request->string('payment')->value() ?: null,
        ];
    }
}
