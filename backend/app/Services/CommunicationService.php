<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Communication;
use App\Models\Employee;
use App\Models\Entity;
use App\Models\Member;
use App\Models\QuotaReminderSetting;
use App\Notifications\MassEmailNotification;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

/**
 * Resuelve destinatarios, envía emails masivos y registra la comunicación en el historial.
 */
class CommunicationService
{
    /**
     * Socios de una entidad que cumplen los filtros y tienen email.
     *
     * @param  array{status?: ?string, member_type_id?: ?string, payment?: ?string}  $filters
     * @return Collection<int, Member>
     */
    public function socios(Entity $entity, array $filters): Collection
    {
        $year = $entity->fiscal_year;

        return $entity->members()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->when(! empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['member_type_id']), fn ($q) => $q->where('member_type_id', $filters['member_type_id']))
            ->when(($filters['payment'] ?? null) === 'pagado', fn ($q) => $q->whereHas(
                'payments',
                fn ($p) => $p->where('year', $year)->where('status', 'pagado')
            ))
            ->when(($filters['payment'] ?? null) === 'pendiente', fn ($q) => $q->whereDoesntHave(
                'payments',
                fn ($p) => $p->where('year', $year)->where('status', 'pagado')
            ))
            ->get();
    }

    /**
     * Empleados (de una empresa o de todas) con email.
     *
     * @return Collection<int, Employee>
     */
    public function empleados(?string $companyId): Collection
    {
        return Employee::query()
            ->where('active', true)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where(fn ($q) => $q->whereNotNull('email_personal')->orWhereNotNull('email_company'))
            ->get();
    }

    /**
     * Envía el email a una lista de direcciones (cola por defecto).
     *
     * @param  iterable<int, string>  $emails
     */
    public function send(iterable $emails, string $subject, string $body): int
    {
        $count = 0;
        foreach ($emails as $email) {
            if (! is_string($email) || trim($email) === '') {
                continue;
            }
            Notification::route('mail', $email)->notify(new MassEmailNotification($subject, $body));
            $count++;
        }

        return $count;
    }

    /**
     * Procesa los recordatorios de cuota del tenant ACTIVO para una fecha dada.
     * Avisa a los socios con cuota pendiente cuando faltan `days_before` días para el
     * cierre del ejercicio (31/dic del año fiscal). Idempotente por día (`last_run_on`).
     */
    public function runQuotaReminders(CarbonInterface $today): int
    {
        $sent = 0;

        $settings = QuotaReminderSetting::query()->where('enabled', true)->with('entity')->get();

        foreach ($settings as $setting) {
            $entity = $setting->entity;
            if ($entity === null) {
                continue;
            }
            if ($setting->last_run_on !== null && $setting->last_run_on->isSameDay($today)) {
                continue;
            }

            $target = Carbon::create($entity->fiscal_year, 12, 31)->subDays($setting->days_before);
            if (! $target->isSameDay($today)) {
                continue;
            }

            $recipients = $this->socios($entity, ['payment' => 'pendiente']);
            $count = $this->send($recipients->pluck('email'), $setting->subject, $setting->body);
            $this->log('socios', $setting->subject, $setting->body, $count, $entity->id, ['payment' => 'pendiente'], 'recordatorio_cuota');

            $setting->update(['last_run_on' => $today->toDateString()]);
            $sent += $count;
        }

        return $sent;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function log(
        string $audience,
        string $subject,
        string $body,
        int $count,
        ?string $entityId = null,
        array $filters = [],
        string $trigger = 'manual',
        ?string $sentBy = null,
    ): Communication {
        return Communication::create([
            'audience' => $audience,
            'entity_id' => $entityId,
            'subject' => $subject,
            'body' => $body,
            'filters' => $filters,
            'recipients_count' => $count,
            'trigger' => $trigger,
            'sent_by' => $sentBy,
        ]);
    }
}
