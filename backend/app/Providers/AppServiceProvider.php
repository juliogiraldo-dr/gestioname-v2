<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Migraciones del sistema (schema `public`): se ejecutan con el `migrate` normal
        // y en los tests. Las migraciones de tenant (database/migrations/tenant) NO se
        // autocargan: se aplican por schema vía `tenant:create` / `migrate:tenants`.
        $this->loadMigrationsFrom(database_path('migrations/system'));

        // En tests usamos una única BD SQLite sin schemas: el TenantMiddleware no cambia
        // el search_path, así que las tablas de tenant deben existir en esa misma BD.
        // Por eso aquí (y SOLO en testing) también autocargamos las migraciones de tenant.
        if ($this->app->environment('testing')) {
            $this->loadMigrationsFrom(database_path('migrations/tenant'));
        }

        // Rate limit de autenticación: 5 intentos/min por IP (login y magic-link).
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }
}
