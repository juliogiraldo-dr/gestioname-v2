<?php

use App\Http\Middleware\EnforcePlanLimit;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias para rutas de tenant: identifica el tenant por subdominio y fija el
        // search_path de PostgreSQL. Se aplicará a los grupos de rutas de negocio.
        $middleware->alias([
            'tenant' => TenantMiddleware::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'plan.limit' => EnforcePlanLimit::class,
        ]);

        // El TenantMiddleware DEBE ejecutarse antes que `auth:sanctum`: el guard de
        // Sanctum consulta `personal_access_tokens` en el schema del tenant, así que el
        // search_path tiene que estar fijado antes. `Authenticate` está en la lista de
        // prioridad de Laravel y, sin esto, se adelantaría al middleware de tenant.
        $middleware->prependToPriorityList(
            before: EncryptCookies::class,
            prepend: TenantMiddleware::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
