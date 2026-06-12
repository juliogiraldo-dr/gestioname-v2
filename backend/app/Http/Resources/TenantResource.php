<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Tenant
 */
class TenantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'subdomain' => $this->subdomain,
            'custom_domain' => $this->custom_domain,
            'status' => $this->status,
            'plan_id' => $this->plan_id,
            'plan' => $this->whenLoaded('subscriptionPlan', fn () => $this->subscriptionPlan === null ? null : [
                'id' => $this->subscriptionPlan->id,
                'name' => $this->subscriptionPlan->name,
                'slug' => $this->subscriptionPlan->slug,
                'price_monthly' => $this->subscriptionPlan->price_monthly,
            ]),
            'override' => $this->whenLoaded('override', fn () => $this->override === null ? null : [
                'limits' => $this->override->limits,
                'modules_allowed' => $this->override->modules_allowed,
            ]),
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'trial_days_left' => $this->trial_ends_at !== null && $this->trial_ends_at->isFuture()
                ? (int) ceil(now()->diffInDays($this->trial_ends_at, absolute: true))
                : null,
            'employees_count' => $this->employees_count ?? null,
            'members_count' => $this->members_count ?? null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
