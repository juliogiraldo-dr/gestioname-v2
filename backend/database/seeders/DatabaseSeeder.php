<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder por defecto (`php artisan db:seed`). Se ejecuta en el contexto del schema
 * `public`, donde NO existe la tabla `users` (los usuarios viven en cada tenant).
 *
 * Para sembrar datos de un tenant usa los seeders específicos, p. ej.:
 *   php artisan db:seed --class=TenantDemoSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,       // planes globales (schema public)
            TenantDemoSeeder::class,
        ]);
    }
}
