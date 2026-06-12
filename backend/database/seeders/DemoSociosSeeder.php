<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyGroup;
use App\Models\Employee;
use App\Models\Entity;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Services\CompanyService;
use App\Services\Socios\EntityService;
use App\Services\Socios\ExpenseService;
use App\Services\Socios\MemberPaymentService;
use App\Services\Socios\MemberService;
use App\Support\TenantSchema;
use Illuminate\Database\Seeder;

/**
 * Datos de ejemplo del tenant `demo` para probar el dashboard admin sin crear datos a mano:
 *   - Módulos del tenant activados (RRHH, Socios, Tesorería).
 *   - Un grupo "Grupo Datarecover" con dos empresas y empleados (módulo RRHH).
 *   - Una entidad independiente tipo peña ("Peña La Aguela") con tipos de socio, socios,
 *     pagos y gastos (módulo Socios/Tesorería).
 *
 *   php artisan db:seed --class=DemoSociosSeeder
 *
 * Idempotente y operando dentro del schema del tenant `demo`.
 */
class DemoSociosSeeder extends Seeder
{
    private const SUBDOMAIN = 'demo';

    private const COMPANY_CIF = 'B86000001';

    private const ENTITY_NAME = 'Peña La Aguela';

    public function run(): void
    {
        if (! Tenant::query()->where('subdomain', self::SUBDOMAIN)->exists()) {
            $this->command?->warn("El tenant '".self::SUBDOMAIN."' no existe. Ejecuta antes TenantDemoSeeder.");

            return;
        }

        TenantSchema::use(self::SUBDOMAIN);

        try {
            TenantModule::syncCatalog();
            $this->seedCompanies();
            $this->seedEntity();
        } finally {
            TenantSchema::usePublic();
        }
    }

    /** Grupo "Grupo Datarecover" con dos empresas y empleados de ejemplo. */
    private function seedCompanies(): void
    {
        $group = CompanyGroup::query()->firstOrCreate(['name' => 'Grupo Datarecover']);

        $company = Company::query()->where('cif', self::COMPANY_CIF)->first();

        if ($company === null) {
            // CompanyService crea además los hitos ENTRADA/SALIDA.
            $company = app(CompanyService::class)->create([
                'company_group_id' => $group->id,
                'name' => 'Datarecover Demo S.L.',
                'cif' => self::COMPANY_CIF,
                'email' => 'demo@datarecover.example',
                'phone' => '918000000',
                'address' => 'C/ Mayor, 10 · 28220 Majadahonda',
            ]);
        } elseif ($company->company_group_id === null) {
            $company->update(['company_group_id' => $group->id]);
        }

        if (! Company::query()->where('cif', 'B86000002')->exists()) {
            app(CompanyService::class)->create([
                'company_group_id' => $group->id,
                'name' => 'Datarecover Cloud S.L.',
                'cif' => 'B86000002',
                'email' => 'cloud@datarecover.example',
            ]);
        }

        if (Employee::query()->where('company_id', $company->id)->count() === 0) {
            $employees = [
                ['Marta', 'Sáenz', 'Dirección', 'Gerente', '10000001'],
                ['Pablo', 'Ortega', 'Desarrollo', 'Desarrollador', '10000002'],
                ['Elena', 'Navarro', 'Soporte', 'Técnica de soporte', '10000003'],
                ['Hugo', 'Castro', 'Comercial', 'Comercial', '10000004'],
            ];

            foreach ($employees as [$first, $last, $department, $position, $code]) {
                Employee::create([
                    'company_id' => $company->id,
                    'first_name' => $first,
                    'last_name' => $last,
                    'department' => $department,
                    'job_position' => $position,
                    'clock_code' => $code,
                    'hire_date' => '2026-01-07',
                    'active' => true,
                ]);
            }
        }
    }

    /** Entidad independiente con tipos de socio, socios, pagos y gastos. */
    private function seedEntity(): void
    {
        if (Entity::query()->where('name', self::ENTITY_NAME)->exists()) {
            $this->command?->warn("La entidad '".self::ENTITY_NAME."' ya existe. Nada que hacer.");

            return;
        }

        $adminId = User::query()->where('email', 'admin@demo.gestioname.app')->value('id');

        // Entidad (EntityService crea además las categorías de gasto por defecto).
        $entity = app(EntityService::class)->create([
            'name' => self::ENTITY_NAME,
            'type' => 'pena',
            'cif' => 'G99000099',
            'address' => 'Plaza Mayor, 1 · 28220 Majadahonda',
            'phone' => '600112233',
            'email' => 'contacto@penalaaguela.example',
            'opening_balance' => 1500,
            'fiscal_year' => 2026,
        ]);

        $types = [
            'adulto' => $entity->memberTypes()->create(['name' => 'Adulto', 'fee_amount' => 60, 'fee_periodicity' => 'anual']),
            'juvenil' => $entity->memberTypes()->create(['name' => 'Juvenil', 'fee_amount' => 30, 'fee_periodicity' => 'anual']),
            'honor' => $entity->memberTypes()->create(['name' => 'De honor', 'fee_amount' => 0, 'fee_periodicity' => 'anual']),
        ];

        $memberService = app(MemberService::class);
        $paymentService = app(MemberPaymentService::class);

        $roster = [
            ['Ana', 'López Vidal', 'adulto', 'activo', ['status' => 'pagado', 'payment_method' => 'transferencia']],
            ['Luis', 'Pérez Gómez', 'adulto', 'activo', ['status' => 'pagado', 'payment_method' => 'efectivo']],
            ['María', 'García Ortiz', 'adulto', 'activo', ['status' => 'pendiente']],
            ['Carlos', 'Ruiz Mena', 'juvenil', 'activo', ['status' => 'pagado', 'payment_method' => 'bizum']],
            ['Sofía', 'Marín Cano', 'juvenil', 'activo', ['status' => 'parcial', 'amount' => 15, 'payment_method' => 'efectivo']],
            ['Javier', 'Sanz Aragón', 'adulto', 'baja_voluntaria', null],
            ['Lucía', 'Torres Vega', 'honor', 'honor', null],
            ['Diego', 'Moreno Gil', 'adulto', 'pendiente', ['status' => 'pendiente']],
        ];

        foreach ($roster as [$first, $last, $typeKey, $status, $payment]) {
            $member = $memberService->create($entity, [
                'first_name' => $first,
                'last_name' => $last,
                'member_type_id' => $types[$typeKey]->id,
                'status' => $status,
                'email' => mb_strtolower($first).'@example.com',
                'date_join' => '2026-01-01',
            ]);

            if ($payment !== null) {
                $paymentService->create($member, array_merge([
                    'year' => 2026,
                    'payment_date' => $payment['status'] === 'pendiente' ? null : '2026-01-20',
                ], $payment), $adminId);
            }
        }

        $categories = $entity->expenseCategories()->pluck('id', 'name');
        $expenseService = app(ExpenseService::class);

        $expenses = [
            ['Alquiler local', '2026-01-15', 300, 'Alquiler del local enero-marzo'],
            ['Material', '2026-02-10', 120, 'Material deportivo y banderas'],
            ['Actos y eventos', '2026-03-05', 450, 'Fiesta patronal de primavera'],
            ['Seguros', '2026-04-01', 90, 'Seguro de responsabilidad civil'],
        ];

        foreach ($expenses as [$categoryName, $date, $amount, $description]) {
            $expenseService->create($entity, [
                'category_id' => $categories[$categoryName] ?? null,
                'date' => $date,
                'amount' => $amount,
                'description' => $description,
            ], $adminId);
        }

        $this->command?->info(
            "Entidad '".self::ENTITY_NAME."' creada con ".count($roster).' socios y '.count($expenses).' gastos.'
        );
    }
}
