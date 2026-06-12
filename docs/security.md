# Seguridad y Cumplimiento Normativo

---

## ET art. 34.9 — Registro de jornada

### Obligaciones
- Registro diario de jornada con hora de inicio y fin.
- Conservación mínima **4 años**.
- Accesible para empleados, representantes sindicales e Inspección de Trabajo.
- Las correcciones son legalmente permitidas pero deben dejar rastro.

### Implementación
- Tabla `attendances`: **de solo escritura**. Nunca UPDATE ni DELETE sin audit log.
- Tabla `attendance_corrections`: registro inmutable de cada modificación.
- Ver ADR-005 para la decisión de arquitectura.

### Verificación
```sql
-- Verificar que no hay registros borrados sin rastro
-- (debería devolver 0 en producción)
SELECT COUNT(*) FROM attendance_corrections
WHERE reason IS NULL OR reason = '';
```

---

## LOPD / GDPR

### Datos personales almacenados

| Dato | Tabla | Clasificación | Medida |
|---|---|---|---|
| DNI/NIE | employees, members | Sensible | Cifrado en BD |
| IBAN | employees | Sensible | Cifrado en BD |
| Nombre completo | employees, members, users | Personal | — |
| Email | users | Personal | — |
| Teléfono | employees, members | Personal | — |
| Fecha de nacimiento | employees, members | Personal | — |
| Datos de salud (bajas) | leave_requests | Muy sensible | Acceso restringido a admin/RRHH |
| Geolocalización | attendances | Personal | Opcional, configurable por empresa |
| Foto | employees | Personal | Almacenamiento local, no CDN externo |

### Cifrado de campos sensibles

```php
// En el modelo Employee:
use Illuminate\Database\Eloquent\Casts\Attribute;

protected function dni(): Attribute
{
    return Attribute::make(
        get: fn ($value) => decrypt($value),
        set: fn ($value) => encrypt($value),
    );
}

protected function iban(): Attribute
{
    return Attribute::make(
        get: fn ($value) => decrypt($value),
        set: fn ($value) => encrypt($value),
    );
}
```

### Derechos del interesado

| Derecho | Implementación |
|---|---|
| Acceso | Endpoint `GET /me` devuelve todos los datos del usuario |
| Rectificación | Endpoint `PUT /me` + edición por admin |
| Supresión | Endpoint admin `DELETE /employees/{id}` (anonimiza, no borra por ET 34.9) |
| Portabilidad | Endpoint `GET /me/export` devuelve JSON con todos sus datos |
| Oposición | Configuración de geolocalización por empleado |

### Anonimización vs borrado

Por la obligación de conservación ET 34.9, los registros de fichaje no se pueden borrar.
Al dar de baja un empleado:

```php
// NO borrar — anonimizar
$employee->update([
    'first_name' => 'ANONIMIZADO',
    'last_name' => '',
    'dni' => encrypt('ANONIMIZADO'),
    'email_personal' => null,
    'phone_personal' => null,
    'iban' => encrypt('ANONIMIZADO'),
    'active' => false,
    'anonymized_at' => now(),
]);
// Los registros de attendance quedan con employee_id pero sin datos personales
```

### DPA por tenant

Cada tenant que contrate el servicio firma un Acuerdo de Encargado de Tratamiento (DPA).
Datarecover actúa como **Encargado del Tratamiento**; el tenant es el **Responsable**.

---

## Autenticación y autorización

### Tokens Sanctum

```php
// Configuración en config/sanctum.php
'expiration' => 60 * 24,  // 24 horas
'token_prefix' => 'gme_',

// Los tokens se revocan al hacer logout
// Rate limiting en rutas de auth: 5 intentos/minuto por IP
```

### Magic Links

```php
// Los magic links expiran en 15 minutos
// Un token de magic link solo puede usarse una vez
// Se almacenan hasheados en BD (nunca en texto plano)
```

### RBAC — permisos por rol

```php
// Roles: super-admin, admin, rrhh-coordinator, operator, employee, member
// Permisos granulares con Spatie Permission

// Ejemplos de permisos asignados por rol:
admin:
  - employees.view, employees.create, employees.edit, employees.delete
  - attendance.view, attendance.correct
  - leave-requests.approve, leave-requests.reject
  - members.view, members.create, members.edit

rrhh-coordinator:
  - employees.view (solo subordinados)
  - attendance.view (solo subordinados)
  - leave-requests.approve (solo subordinados)

employee:
  - me.view, me.edit
  - me.clock
  - me.leave-request.create

member:
  - member-portal.view
  - member-portal.events.attend
```

### Control IP por empleado

```php
// Si el empleado tiene IPs configuradas, se valida en cada fichaje
if ($employee->allowed_ips->isNotEmpty()) {
    $clientIp = $request->ip();
    if (!$employee->allowed_ips->contains('ip_address', $clientIp)) {
        throw new AttendanceException('IP_NOT_ALLOWED');
    }
}
```

---

## Seguridad en tránsito

- TLS 1.3 obligatorio en producción (Traefik + Let's Encrypt).
- HSTS habilitado: `Strict-Transport-Security: max-age=31536000`.
- Headers de seguridad en Nginx:
  ```
  X-Content-Type-Options: nosniff
  X-Frame-Options: DENY
  X-XSS-Protection: 1; mode=block
  Referrer-Policy: strict-origin-when-cross-origin
  ```

---

## Auditoría

### Eventos que se registran en `audit_logs`

```php
// Fichajes
attendance.clock_in
attendance.clock_in_alert_double_entry
attendance.correction
attendance.manual_add
attendance.delete

// Empleados
employee.create
employee.update
employee.deactivate
employee.anonymize

// Nóminas (acceso a datos sensibles)
payslip.view
payslip.download

// Socios
member.create
member.payment.create
member.payment.delete

// Exportaciones
suenlace.export
report.work_time_record.generate

// Auth
auth.login
auth.login_failed
auth.logout
auth.magic_link_send
```

### Consultar auditoría

```bash
# Via tinker
php artisan tinker
> AuditLog::where('action', 'attendance.correction')->latest()->take(10)->get()

# Via API (solo super-admin)
GET /api/v1/admin/audit-logs?action=attendance.correction&date_from=2026-06-01
```

---

## Backups y recuperación

| Frecuencia | Tipo | Retención | Destino |
|---|---|---|---|
| Cada hora | Snapshot Redis (sesiones, colas) | 24 horas | Disco local |
| Diario 02:00 | pg_dump por schema | 30 días | S3/MinIO cifrado |
| Semanal domingo | pg_dump full | 12 semanas | S3/MinIO cifrado + offsite |
| Mensual | Snapshot completo servidor | 6 meses | Offsite |

### Verificación de backups

```bash
# Test de restauración mensual (script automatizado)
scripts/test-backup-restore.sh {tenant_id}
# Restaura en servidor de test, verifica integridad, notifica por email
```

### RTO / RPO objetivo

- **RPO** (Recovery Point Objective): máximo 1 hora de pérdida de datos.
- **RTO** (Recovery Time Objective): restauración en menos de 4 horas.
