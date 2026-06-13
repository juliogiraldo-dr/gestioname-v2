<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

/**
 * Plan de cuentas base para asociaciones y pequeñas empresas. Idempotente (por código).
 * Debe ejecutarse con el schema del tenant ya activo.
 */
class ChartOfAccountsSeeder extends Seeder
{
    /** @var list<array{code: string, name: string, type: string}> */
    public const ACCOUNTS = [
        ['code' => '100', 'name' => 'Capital / Patrimonio', 'type' => 'patrimonio'],
        ['code' => '110', 'name' => 'Reservas', 'type' => 'patrimonio'],
        ['code' => '120', 'name' => 'Resultado del ejercicio', 'type' => 'patrimonio'],
        ['code' => '200', 'name' => 'Inmovilizado', 'type' => 'activo'],
        ['code' => '300', 'name' => 'Existencias', 'type' => 'activo'],
        ['code' => '430', 'name' => 'Socios / Clientes', 'type' => 'activo'],
        ['code' => '440', 'name' => 'Deudores', 'type' => 'activo'],
        ['code' => '460', 'name' => 'Personal (anticipos)', 'type' => 'activo'],
        ['code' => '470', 'name' => 'HP acreedora (IVA)', 'type' => 'pasivo'],
        ['code' => '480', 'name' => 'HP deudora (IVA soportado)', 'type' => 'activo'],
        ['code' => '520', 'name' => 'Deudas a corto plazo', 'type' => 'pasivo'],
        ['code' => '570', 'name' => 'Caja', 'type' => 'activo'],
        ['code' => '572', 'name' => 'Bancos', 'type' => 'activo'],
        ['code' => '600', 'name' => 'Compras / Gastos', 'type' => 'gasto'],
        ['code' => '700', 'name' => 'Ventas / Ingresos', 'type' => 'ingreso'],
        ['code' => '720', 'name' => 'Cuotas de socios', 'type' => 'ingreso'],
        ['code' => '740', 'name' => 'Subvenciones', 'type' => 'ingreso'],
        ['code' => '750', 'name' => 'Otros ingresos', 'type' => 'ingreso'],
    ];

    public function run(): void
    {
        foreach (self::ACCOUNTS as $account) {
            Account::query()->firstOrCreate(['code' => $account['code']], [
                'name' => $account['name'],
                'type' => $account['type'],
                'active' => true,
            ]);
        }
    }
}
