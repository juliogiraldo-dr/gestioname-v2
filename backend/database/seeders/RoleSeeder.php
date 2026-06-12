<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Crea los 6 roles del sistema en el schema del tenant ACTIVO.
 * El llamante debe haber fijado el search_path al schema correcto antes de invocar.
 *
 * Guard `web`: es el guard por defecto (config/auth.php) y el que resuelve el modelo
 * User, tanto si la request se autentica por sesión como por token Sanctum.
 */
class RoleSeeder extends Seeder
{
    public const GUARD = 'web';

    /** @var list<string> */
    public const ROLES = [
        'super-admin',
        'admin',
        'rrhh-coordinator',
        'operator',
        'employee',
        'member',
        // Gestoría externa: nóminas, informes RRHH y descargas. Sin acceso a datos
        // sensibles (DNI/IBAN) ni a la modificación de empleados, fichajes o configuración.
        'gestoria',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ROLES as $role) {
            Role::findOrCreate($role, self::GUARD);
        }
    }
}
