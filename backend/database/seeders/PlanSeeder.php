<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Planes base de Gestioname (schema public). Idempotente: no sobrescribe planes ya
 * existentes (el super-admin puede haberlos editado). Un límite null = ilimitado.
 *
 *   php artisan db:seed --class=PlanSeeder
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free', 'slug' => 'free', 'price_monthly' => 0, 'price_yearly' => 0,
                'limits' => ['companies' => 1, 'employees' => 10, 'entities' => 1, 'members' => 30, 'users' => 1],
                'modules_allowed' => ['rrhh', 'socios', 'tesoreria'],
            ],
            [
                'name' => 'Starter', 'slug' => 'starter', 'price_monthly' => 9.90, 'price_yearly' => 99,
                'limits' => ['companies' => 1, 'employees' => 25, 'entities' => 2, 'members' => 80, 'users' => 3],
                'modules_allowed' => ['rrhh', 'socios', 'tesoreria', 'comunicaciones'],
            ],
            [
                'name' => 'Professional', 'slug' => 'professional', 'price_monthly' => 19.90, 'price_yearly' => 199,
                'limits' => ['companies' => 3, 'employees' => 100, 'entities' => 10, 'members' => 500, 'users' => 10],
                'modules_allowed' => ['rrhh', 'socios', 'tesoreria', 'comunicaciones', 'informes_avanzados', 'white_label'],
            ],
            [
                'name' => 'Business', 'slug' => 'business', 'price_monthly' => 39.90, 'price_yearly' => 399,
                'limits' => ['companies' => null, 'employees' => null, 'entities' => null, 'members' => null, 'users' => null],
                'modules_allowed' => ['rrhh', 'socios', 'tesoreria', 'comunicaciones', 'informes_avanzados', 'white_label', 'nominas', 'multitenant'],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->firstOrCreate(['slug' => $plan['slug']], [
                'name' => $plan['name'],
                'price_monthly' => $plan['price_monthly'],
                'price_yearly' => $plan['price_yearly'],
                'is_public' => true,
                'limits' => $plan['limits'],
                'modules_allowed' => $plan['modules_allowed'],
            ]);
        }
    }
}
