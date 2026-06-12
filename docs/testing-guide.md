# Guía de Testing — Gestioname v2

---

## Filosofía

- Tests de integración (Feature) para todos los endpoints de la API.
- Tests unitarios (Unit) para Services con lógica de negocio compleja.
- Tests E2E (Playwright) para flujos críticos del usuario en frontend.
- **Cobertura mínima requerida**: 70% en Services, 60% global.
- Sin tests → el PR no se mergea.

---

## Backend — PHPUnit

```bash
# Ejecutar todos los tests
php artisan test

# Solo un directorio
php artisan test tests/Feature/Attendance/

# Solo un fichero
php artisan test tests/Feature/Auth/AuthApiTest.php

# Solo tests con nombre específico
php artisan test --filter=testClockInCreatesAttendance

# Con cobertura HTML (requiere Xdebug)
php artisan test --coverage --coverage-html=coverage/
```

### Configuración de tests

Los tests usan una base de datos separada en memoria (SQLite) o un schema temporal en PostgreSQL:

```php
// tests/TestCase.php
use RefreshDatabase;  // Limpia y remigra en cada test

// Para tests de tenant:
protected function setUp(): void
{
    parent::setUp();
    $this->tenant = Tenant::factory()->create();
    $this->actingAsTenant($this->tenant);
}
```

### Tests requeridos por módulo

**Autenticación**
```
tests/Feature/Auth/
  LoginTest.php              — email+pass correcto, incorrecto, rate limit
  MagicLinkTest.php          — envío, verificación, expiración
  LogoutTest.php             — revocación de token
```

**Multi-tenancy**
```
tests/Feature/Tenant/
  TenantMiddlewareTest.php   — subdominio válido, inválido, tenant suspendido
  TenantIsolationTest.php    — un tenant no puede acceder a datos de otro
  TenantMigrationTest.php    — creación de schema, migraciones
```

**Fichajes (CRÍTICO — ET 34.9)**
```
tests/Feature/Attendance/
  ClockInTest.php            — PIN válido, inválido, geolocalización, IP restringida
  DoubleEntryTest.php        — alerta doble entrada
  CorrectionTest.php         — corrección crea audit log, original no se modifica
  ImmutabilityTest.php       — no se puede borrar attendance sin audit log

tests/Unit/
  AttendanceServiceTest.php  — cálculo horas trabajadas, sobretiempo, retrasos
  RegistroHorarioTest.php    — generación Excel/PDF cumple ET 34.9
```

**Socios y tesorería**
```
tests/Feature/Members/
  MemberApiTest.php          — CRUD socios
  MemberImportTest.php       — importación Excel con plantilla
  PaymentApiTest.php         — CRUD pagos, estados

tests/Unit/
  TreasuryCalculationTest.php — saldo = opening + ingresos - gastos
```

**Suenlace.dat (CRÍTICO — integración a3asesor)**
```
tests/Feature/Suenlace/
  SuenlaceExportTest.php     — genera fichero, 512 bytes por registro, CR+LF
  SuenlaceType0Test.php      — apuntes sin IVA
  SuenlaceType1Test.php      — facturas con IVA
  SuenlaceTypeNTest.php      — registro Modelo 190 nóminas
  SuenlaceValidationTest.php — validaciones previas a exportación
```

Fixtures de referencia en `tests/fixtures/suenlace/`:
- `gastos-simple.dat` — 3 asientos de gasto sin IVA
- `factura-iva.dat` — factura recibida con IVA 21%
- `nomina-modelo190.dat` — asiento de nómina con registro N

### Ejemplo test de corrección inmutable

```php
/** @test */
public function correction_creates_audit_log_and_does_not_modify_original()
{
    $employee = Employee::factory()->create();
    $attendance = Attendance::factory()->create([
        'employee_id' => $employee->id,
        'clocked_at' => '2026-06-11 09:00:00',
    ]);

    $this->actingAs($this->adminUser)
         ->putJson("/api/v1/attendance/{$attendance->id}", [
             'new_clocked_at' => '2026-06-11 09:05:00',
             'reason' => 'El empleado llegó 5 minutos antes de lo registrado',
         ])
         ->assertOk();

    // El registro original NO se modifica
    $this->assertDatabaseHas('attendances', [
        'id' => $attendance->id,
        'clocked_at' => '2026-06-11 09:00:00',
    ]);

    // Se crea la corrección
    $this->assertDatabaseHas('attendance_corrections', [
        'attendance_id' => $attendance->id,
        'old_clocked_at' => '2026-06-11 09:00:00',
        'new_clocked_at' => '2026-06-11 09:05:00',
    ]);

    // Se crea un nuevo registro attendance con el valor corregido
    $this->assertDatabaseHas('attendances', [
        'employee_id' => $employee->id,
        'clocked_at' => '2026-06-11 09:05:00',
    ]);
}
```

---

## Frontend — Playwright (E2E)

```bash
# Instalar Playwright
cd frontend && npx playwright install

# Ejecutar todos los tests E2E
npx playwright test

# Solo un fichero
npx playwright test tests/e2e/clock.spec.ts

# Modo UI (visual)
npx playwright test --ui

# Modo headed (ver el navegador)
npx playwright test --headed
```

### Tests E2E requeridos

```
frontend/tests/e2e/
  auth.spec.ts               — login, logout, magic link
  clock.spec.ts              — fichar con PIN, alerta doble entrada
  leave-request.spec.ts      — solicitar ausencia → aprobación coordinador
  members.spec.ts            — alta socio, registrar pago
  treasury.spec.ts           — verificar saldo actualizado tras pago
```

### Ejemplo test E2E fichaje

```typescript
// tests/e2e/clock.spec.ts
test('empleado puede fichar entrada con PIN', async ({ page }) => {
  await page.goto('http://demo.localhost:3000/clock');

  // Introducir PIN
  for (const digit of '12345678') {
    await page.click(`[data-digit="${digit}"]`);
  }

  // Seleccionar hito ENTRADA
  await page.click('[data-milestone="entrada"]');

  // Verificar confirmación
  await expect(page.locator('[data-testid="clock-success"]')).toBeVisible();
  await expect(page.locator('[data-testid="employee-name"]'))
    .toContainText('Juan García');
});
```

---

## CI — GitHub Actions

El pipeline CI ejecuta automáticamente en cada PR:

```yaml
# .github/workflows/ci.yml
jobs:
  backend-tests:
    - composer install
    - php artisan test --coverage
    - php vendor/bin/pint --test   # Lint

  frontend-tests:
    - npm install
    - npm run typecheck
    - npm run lint
    - npx playwright test
```

El merge a `main` está bloqueado si alguno de estos jobs falla.
