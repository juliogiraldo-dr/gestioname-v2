<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Tenant;
use App\Notifications\UpgradeRequestNotification;
use App\Services\PlanLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class SubscriptionController extends Controller
{
    private const RESOURCES = ['companies', 'employees', 'entities', 'members', 'users'];

    private const CONTACT_EMAIL = 'info@datarecover.es';

    public function __construct(private readonly PlanLimitService $limits) {}

    /** Suscripción del tenant actual: plan, trial, uso vs límites y planes disponibles. */
    public function show(): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');
        $resolved = $this->limits->resolve($tenant);
        $plan = $tenant->subscriptionPlan;

        $usage = [];
        foreach (self::RESOURCES as $r) {
            $usage[$r] = ['used' => $this->limits->usage($r), 'limit' => $resolved['limits'][$r] ?? null];
        }

        return response()->json(['data' => [
            'plan' => $plan === null ? null : [
                'name' => $plan->name, 'slug' => $plan->slug, 'price_monthly' => $plan->price_monthly,
            ],
            'trial_ends_at' => $tenant->trial_ends_at?->toIso8601String(),
            'trial_days_left' => $tenant->trial_ends_at !== null && $tenant->trial_ends_at->isFuture()
                ? (int) ceil(now()->diffInDays($tenant->trial_ends_at, absolute: true))
                : null,
            'usage' => $usage,
            'plans' => Plan::query()->where('is_public', true)->orderBy('price_monthly')
                ->get(['name', 'slug', 'price_monthly', 'price_yearly', 'limits', 'modules_allowed']),
        ]]);
    }

    /** Solicitud de cambio de plan: envía un email a Datarecover. */
    public function requestUpgrade(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'plan' => ['required', 'string', 'max:100'],
        ]);

        Notification::route('mail', self::CONTACT_EMAIL)->notify(
            new UpgradeRequestNotification($tenant->name, $data['name'], $data['email'], $data['plan'])
        );

        return response()->json(['message' => 'Solicitud enviada. Te contactaremos en breve.']);
    }
}
