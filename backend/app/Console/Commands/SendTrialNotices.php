<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TrialExpiredNotification;
use App\Notifications\TrialExpiringNotification;
use App\Support\TenantSchema;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Avisos de trial: 7 días antes de caducar y el día que caduca. Pensado para correr
 * a diario desde el scheduler. Envía al admin de cada tenant.
 *
 *   php artisan trials:notify
 *   php artisan trials:notify --date=2026-07-01   (simula la fecha de hoy)
 */
class SendTrialNotices extends Command
{
    protected $signature = 'trials:notify {--date= : Fecha a simular (Y-m-d)}';

    protected $description = 'Avisa a los tenants cuyo trial caduca en 7 días o acaba de caducar';

    public function handle(): int
    {
        $today = $this->option('date') ? Carbon::parse($this->option('date'))->startOfDay() : Carbon::today();
        $sent = 0;

        $tenants = Tenant::query()
            ->where('status', 'active')
            ->whereNotNull('trial_ends_at')
            ->get();

        foreach ($tenants as $tenant) {
            $endsOn = $tenant->trial_ends_at->copy()->startOfDay();
            $daysLeft = $today->diffInDays($endsOn, false);

            $notification = match (true) {
                $daysLeft === 7 => new TrialExpiringNotification($tenant->name, 7),
                $daysLeft === 0 => new TrialExpiredNotification($tenant->name),
                default => null,
            };
            if ($notification === null) {
                continue;
            }

            $email = $this->adminEmail($tenant->subdomain);
            if ($email === null) {
                continue;
            }

            Notification::route('mail', $email)->notify($notification);
            $sent++;
        }

        $this->info("Avisos de trial enviados: {$sent}.");

        return self::SUCCESS;
    }

    /** Email del primer admin del tenant (vive en su schema). */
    private function adminEmail(string $subdomain): ?string
    {
        TenantSchema::use($subdomain);
        try {
            return User::query()->role('admin')->value('email');
        } catch (Throwable) {
            return null;
        } finally {
            TenantSchema::usePublic();
        }
    }
}
