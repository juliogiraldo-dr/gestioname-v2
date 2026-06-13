<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ContractExpiringNotification;
use App\Support\TenantSchema;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Alerta de vencimiento de contratos. Recorre los tenants y avisa al admin cuando un
 * contrato vence en 30 o 7 días. Pensado para ejecutarse a diario desde el scheduler.
 *
 *   php artisan alerts:contracts
 *   php artisan alerts:contracts --date=2026-07-01   (simula la fecha de hoy)
 */
class AlertExpiringContracts extends Command
{
    protected $signature = 'alerts:contracts {--date= : Fecha a simular (Y-m-d)}';

    protected $description = 'Avisa al admin de cada tenant de los contratos que vencen en 30 o 7 días';

    /** @var list<int> */
    private const THRESHOLDS = [30, 7];

    public function handle(): int
    {
        $today = $this->option('date') ? Carbon::parse($this->option('date'))->startOfDay() : Carbon::today();
        $sent = 0;

        foreach (Tenant::query()->where('status', 'active')->get() as $tenant) {
            TenantSchema::use($tenant->subdomain);
            try {
                $sent += $this->processTenant($today);
            } catch (Throwable $e) {
                $this->error("Error en '{$tenant->subdomain}': ".$e->getMessage());
            } finally {
                TenantSchema::usePublic();
            }
        }

        $this->info("Avisos de vencimiento de contrato enviados: {$sent}.");

        return self::SUCCESS;
    }

    private function processTenant(Carbon $today): int
    {
        $adminEmail = User::query()->role('admin')->value('email');
        if ($adminEmail === null) {
            return 0;
        }

        $dates = array_map(fn (int $d) => $today->copy()->addDays($d)->toDateString(), self::THRESHOLDS);

        $employees = Employee::query()
            ->where('active', true)
            ->whereNotNull('contract_end_date')
            ->whereIn('contract_end_date', $dates)
            ->get();

        foreach ($employees as $employee) {
            $endDate = $employee->contract_end_date;
            $daysLeft = (int) $today->diffInDays($endDate, false);
            Notification::route('mail', $adminEmail)->notify(
                new ContractExpiringNotification($employee->fullName(), $daysLeft, $endDate->toDateString())
            );
        }

        return $employees->count();
    }
}
