<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\CommunicationService;
use App\Support\TenantSchema;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Recordatorio automático de cuota. Recorre todos los tenants activos y, en cada schema,
 * avisa a los socios con cuota pendiente cuando faltan `days_before` días para el cierre
 * del ejercicio. Pensado para ejecutarse a diario desde el scheduler.
 *
 *   php artisan reminders:quota
 *   php artisan reminders:quota --date=2026-12-16   (simula la fecha de hoy)
 */
class SendQuotaReminders extends Command
{
    protected $signature = 'reminders:quota {--date= : Fecha a simular (Y-m-d), por defecto hoy}';

    protected $description = 'Envía los recordatorios de cuota pendientes en todos los tenants';

    public function handle(CommunicationService $service): int
    {
        $today = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
        $total = 0;

        foreach (Tenant::query()->where('status', 'active')->get() as $tenant) {
            TenantSchema::use($tenant->subdomain);
            try {
                $total += $service->runQuotaReminders($today);
            } catch (Throwable $e) {
                $this->error("Error en '{$tenant->subdomain}': ".$e->getMessage());
            } finally {
                TenantSchema::usePublic();
            }
        }

        $this->info("Recordatorios de cuota enviados: {$total}.");

        return self::SUCCESS;
    }
}
