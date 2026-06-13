<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Módulo activable del tenant.
 *
 * @property string $id
 * @property string $key
 * @property bool $enabled
 */
class TenantModule extends Model
{
    use HasUuids;

    /**
     * Catálogo de módulos: clave => [etiqueta, descripción, activo por defecto].
     * Las claves rrhh/socios/tesoreria están implementadas; nominas/comunicaciones son futuras.
     *
     * @var array<string, array{label: string, description: string, default: bool}>
     */
    public const CATALOG = [
        'rrhh' => ['label' => 'RRHH / Fichajes', 'description' => 'Empleados, control de jornada y ausencias.', 'default' => true],
        'socios' => ['label' => 'Socios / Asociaciones', 'description' => 'Entidades, socios y cuotas.', 'default' => true],
        'tesoreria' => ['label' => 'Tesorería', 'description' => 'Ingresos, gastos y saldo por entidad.', 'default' => true],
        'contabilidad' => ['label' => 'Contabilidad', 'description' => 'Libro de cuentas simplificado: plan de cuentas, asientos e informes.', 'default' => false],
        'nominas' => ['label' => 'Nóminas', 'description' => 'Gestión de nóminas (próximamente).', 'default' => false],
        'comunicaciones' => ['label' => 'Comunicaciones', 'description' => 'Comunicación con empleados y socios (próximamente).', 'default' => false],
        'white_label' => ['label' => 'Marca blanca', 'description' => 'Dominio propio y personalización visual (logo, color, nombre).', 'default' => false],
        'multitenant' => ['label' => 'Multitenant', 'description' => 'Subtenants independientes (próximamente).', 'default' => false],
    ];

    /** @var list<string> */
    protected $fillable = ['key', 'enabled'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    /**
     * Garantiza que existen las filas de todos los módulos del catálogo (idempotente).
     * Debe llamarse con el schema del tenant ya activo.
     */
    public static function syncCatalog(): void
    {
        foreach (self::CATALOG as $key => $meta) {
            self::query()->firstOrCreate(['key' => $key], ['enabled' => $meta['default']]);
        }
    }
}
