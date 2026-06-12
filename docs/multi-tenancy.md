# Multi-tenancy — Guía técnica

> El sistema usa **schemas PostgreSQL separados** por tenant.
> Schema `public` = sistema. Schema `{subdomain}` = datos del tenant.

---

## Cómo funciona

### 1. Identificación del tenant

Cada request identifica el tenant por **subdominio**:

```
empresa1.gestioname.app  →  tenant: empresa1  →  schema: empresa1
empresa2.gestioname.app  →  tenant: empresa2  →  schema: empresa2
admin.gestioname.app     →  super-admin (schema public)
```

En desarrollo local: `empresa1.localhost:3000`

### 2. TenantMiddleware

```php
// app/Http/Middleware/TenantMiddleware.php
class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $subdomain = $this->extractSubdomain($request->getHost());

        $tenant = Tenant::where('subdomain', $subdomain)
                        ->where('status', 'active')
                        ->firstOrFail();  // 404 si no existe

        // Configurar el search_path de PostgreSQL
        DB::statement("SET search_path TO {$tenant->subdomain}, public");

        // Disponible en toda la request
        app()->instance('tenant', $tenant);
        $request->merge(['tenant' => $tenant]);

        return $next($request);
    }

    private function extractSubdomain(string $host): string
    {
        // empresa1.gestioname.app → empresa1
        // empresa1.localhost → empresa1
        $parts = explode('.', $host);
        return $parts[0];
    }
}
```

### 3. Crear un nuevo tenant

```bash
php artisan tenant:create "Nombre Empresa" subdomain
```

Internamente:
```php
// app/Console/Commands/TenantCreate.php
DB::statement("CREATE SCHEMA IF NOT EXISTS {$subdomain}");
DB::statement("SET search_path TO {$subdomain}, public");
Artisan::call('migrate', ['--path' => 'database/migrations/tenant']);
Artisan::call('db:seed', ['--class' => 'TenantBaseSeeder']);
// Crear usuario admin inicial
```

### 4. Migraciones

Las migraciones están separadas en dos carpetas:

```
database/migrations/
├── system/          # Para el schema public (tenants, subscriptions)
└── tenant/          # Para cada schema de tenant (employees, members, etc.)
```

```bash
# Migrar solo schema public
php artisan migrate --path=database/migrations/system

# Migrar UN tenant
php artisan migrate --path=database/migrations/tenant
# (requiere que search_path esté configurado)

# Migrar TODOS los tenants
php artisan migrate:tenants
# Itera sobre todos los tenants activos y migra cada schema
```

```php
// app/Console/Commands/MigrateTenants.php
class MigrateTenants extends Command
{
    public function handle()
    {
        $tenants = Tenant::where('status', 'active')->get();

        foreach ($tenants as $tenant) {
            DB::statement("SET search_path TO {$tenant->subdomain}, public");

            $this->info("Migrando tenant: {$tenant->subdomain}");
            Artisan::call('migrate', [
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);
        }
    }
}
```

---

## Reglas de programación

### ✅ Correcto — Eloquent respeta el search_path automáticamente

```php
// El TenantMiddleware ya configuró el search_path
// Todos los queries van al schema correcto automáticamente
$employees = Employee::where('active', true)->get();  // ✓
$member = Member::find($id);  // ✓
```

### ✅ Correcto — Acceder al tenant actual

```php
$tenant = app('tenant');  // Instancia del tenant en la request actual
$tenant = request()->tenant;
```

### ❌ Incorrecto — Nunca usar schema public para datos de negocio

```php
// NUNCA hacer esto — mezcla datos de tenants
DB::table('public.employees')->get();  // ✗ PELIGROSO
```

### ❌ Incorrecto — Nunca asumir el search_path en código que no sea de tenant

```php
// En un Job que se ejecuta fuera de una request HTTP:
// El search_path NO está configurado automáticamente
// Hay que configurarlo explícitamente:

class ExportJob implements ShouldQueue
{
    public function handle()
    {
        $tenant = Tenant::find($this->tenantId);
        DB::statement("SET search_path TO {$tenant->subdomain}, public");

        // Ahora sí se puede usar Eloquent normalmente
        $employees = Employee::all();
    }
}
```

---

## Jobs y colas — configurar tenant

Los Jobs asíncronos (exportaciones, emails) se ejecutan fuera del ciclo HTTP, por lo que no pasan por el `TenantMiddleware`. Deben configurar el tenant manualmente:

```php
// Patrón recomendado para Jobs de tenant
class GenerateSuenlaceExport implements ShouldQueue
{
    public function __construct(
        private readonly string $tenantId,
        private readonly int $year,
    ) {}

    public function handle(SuenlaceExportService $service): void
    {
        $tenant = Tenant::findOrFail($this->tenantId);

        // Configurar schema
        DB::statement("SET search_path TO {$tenant->subdomain}, public");

        // Ejecutar lógica
        $service->exportAll($this->year);
    }
}
```

Usar el trait `HasTenant` para simplificar:

```php
trait HasTenant
{
    public function setupTenant(string $tenantId): Tenant
    {
        $tenant = Tenant::findOrFail($tenantId);
        DB::statement("SET search_path TO {$tenant->subdomain}, public");
        return $tenant;
    }
}
```

---

## Backups por tenant

```bash
# Backup de UN tenant
pg_dump -h localhost -U gestioname -n empresa1 gestioname > backup_empresa1_$(date +%Y%m%d).sql

# Restaurar un tenant
psql -h localhost -U gestioname gestioname < backup_empresa1_20260611.sql

# Script de backup automático (cron diario)
# scripts/backup-tenants.sh
for tenant in $(php artisan tenant:list --ids); do
  pg_dump -h $DB_HOST -U $DB_USER -n $tenant $DB_NAME | \
    gzip > /backups/${tenant}_$(date +%Y%m%d).sql.gz
done
```

---

## Onboarding de nuevo tenant — flujo completo

```
1. Usuario completa formulario de registro en la landing
2. Controller crea Tenant en schema public con status='trial'
3. Job TenantSetupJob:
   a. CREATE SCHEMA {subdomain}
   b. SET search_path TO {subdomain}
   c. php artisan migrate --path=tenant
   d. Seed: festivos nacionales, categorías de gasto por defecto, hitos ENTRADA/SALIDA
   e. Crear usuario admin con la contraseña del formulario
   f. Enviar email de bienvenida con magic link
4. Redirigir a {subdomain}.gestioname.app/onboarding (wizard de configuración)
5. Wizard: empresa → centro de trabajo → primer empleado → calendario laboral
6. Al completar wizard: tenant.status = 'active', periodo trial activo
```

---

## Límites por plan

El middleware `PlanLimitMiddleware` verifica los límites antes de crear:

```php
// Verificar límite de empleados
if ($tenant->plan === 'free' && Employee::count() >= 10) {
    throw new PlanLimitException('PLAN_LIMIT_REACHED', 'employees', 10);
}

// Verificar límite de socios
if ($tenant->plan === 'essential' && Member::count() >= 80) {
    throw new PlanLimitException('PLAN_LIMIT_REACHED', 'members', 80);
}
```

Límites por plan:

| Plan | Empleados | Socios | Usuarios |
|---|---|---|---|
| free | 10 | 30 | 1 |
| essential | 25 | 80 | 2 |
| professional | 50 | 250 | 5 |
| business | 150 | 800 | ilimitados |
| enterprise | ilimitados | ilimitados | ilimitados |
