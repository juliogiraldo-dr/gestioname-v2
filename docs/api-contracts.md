# API Contracts — Gestioname v2

> Base URL: `https://{subdomain}.gestioname.app/api/v1`
> Autenticación: `Authorization: Bearer {sanctum_token}`
> Content-Type: `application/json`
> Todos los errores siguen el formato estándar de abajo.

---

## Formato de respuesta estándar

```json
// Éxito con datos
{ "data": { ... }, "meta": { ... } }

// Éxito lista paginada
{
  "data": [ ... ],
  "meta": { "current_page": 1, "last_page": 5, "per_page": 20, "total": 98 }
}

// Éxito sin contenido
{ "message": "Operación realizada correctamente" }

// Error de validación (422)
{
  "message": "Los datos proporcionados no son válidos.",
  "errors": { "email": ["El campo email es obligatorio."] }
}

// Error de negocio (400, 403, 404, 409)
{ "message": "Descripción del error", "code": "ERROR_CODE" }
```

---

## Auth

```
POST   /auth/login                  Email + password → token
POST   /auth/magic-link             Email → envía enlace
POST   /auth/magic-link/verify      Token → Sanctum token
POST   /auth/logout                 Revoca token actual
POST   /auth/refresh                Renueva token
GET    /auth/me                     Usuario autenticado + roles
```

**POST /auth/login**
```json
// Request
{ "email": "admin@empresa.gestioname.app", "password": "..." }

// Response 200
{
  "data": {
    "token": "1|abc...",
    "token_type": "Bearer",
    "expires_at": "2026-07-11T17:00:00Z",
    "user": { "id": "uuid", "name": "...", "email": "...", "roles": ["admin"] }
  }
}
```

---

## Empresas y centros

```
GET    /companies                   Lista empresas del tenant
POST   /companies                   Crear empresa
GET    /companies/{id}              Detalle
PUT    /companies/{id}              Actualizar
DELETE /companies/{id}              Eliminar (solo si sin empleados)

GET    /companies/{id}/work-centers
POST   /companies/{id}/work-centers
PUT    /work-centers/{id}
DELETE /work-centers/{id}

GET    /holidays                    Lista festivos (filtro: year, type)
POST   /holidays                    Crear festivo
PUT    /holidays/{id}
DELETE /holidays/{id}

GET    /milestones                  Hitos de fichaje
POST   /milestones
PUT    /milestones/{id}
DELETE /milestones/{id}
```

---

## Convenios y calendarios

```
GET    /agreements
POST   /agreements
GET    /agreements/{id}
PUT    /agreements/{id}
DELETE /agreements/{id}

GET    /agreements/{id}/leave-types
POST   /agreements/{id}/leave-types
PUT    /leave-types/{id}
DELETE /leave-types/{id}

GET    /schedule-templates          Query: year, company_id
POST   /schedule-templates
PUT    /schedule-templates/{id}
DELETE /schedule-templates/{id}

GET    /calendars                   Query: year, company_id
POST   /calendars
GET    /calendars/{id}
PUT    /calendars/{id}
DELETE /calendars/{id}
POST   /calendars/{id}/fill-quick   Llenado rápido
POST   /calendars/{id}/fill-manual  Llenado manual
DELETE /calendars/{id}/clear        Borrado por rango
POST   /calendars/{id}/clone        Clonar a otro año
GET    /calendars/{id}/simulate-vacation  Simulación horas
POST   /calendars/{id}/employees    Asignar empleados
```

**POST /calendars/{id}/fill-quick**
```json
// Request
{
  "weekdays": [1, 2, 3, 4],          // 1=lunes ... 7=domingo
  "months": [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
  "schedule_template_id": "uuid",
  "include_holidays": false
}
// Response 200
{ "message": "Calendario rellenado", "days_filled": 220 }
```

---

## Empleados

```
GET    /employees                   Filtros: company_id, work_center_id, department, status, agreement_id
POST   /employees                   Alta manual
POST   /employees/invite            Alta por invitación email
POST   /employees/import            Alta por Excel (multipart/form-data)
GET    /employees/template          Descargar plantilla Excel
GET    /employees/{id}
PUT    /employees/{id}
PATCH  /employees/{id}/activate
PATCH  /employees/{id}/deactivate

GET    /employees/{id}/contracts
POST   /employees/{id}/contracts
PUT    /contracts/{id}

GET    /employees/{id}/salaries
POST   /employees/{id}/salaries

GET    /employees/{id}/documents
POST   /employees/{id}/documents    multipart/form-data
DELETE /documents/{id}

GET    /employees/{id}/qualifications
POST   /employees/{id}/qualifications
PUT    /qualifications/{id}
DELETE /qualifications/{id}

GET    /employees/{id}/behavior-records
POST   /employees/{id}/behavior-records
GET    /employees/{id}/materials
POST   /employees/{id}/materials

GET    /employees/{id}/bradford      Índice Bradford actual
GET    /employees/{id}/calendar      Calendario del año activo
```

---

## Fichajes

```
POST   /attendance/clock            Fichar (código PIN)
GET    /attendance/daily            Informe diario (query: date, company_id, work_center_id)
GET    /attendance/employee/{id}    Fichajes de un empleado (query: date_from, date_to)
POST   /attendance/manual           Fichaje manual (requiere rol admin/coordinator)
PUT    /attendance/{id}             Corregir fichaje (crea correction + actualiza)
DELETE /attendance/{id}             Eliminar fichaje (crea correction)
GET    /attendance/{id}/corrections Historial de correcciones de un fichaje
```

**POST /attendance/clock**
```json
// Request
{
  "clock_code": "12345678",
  "milestone_id": "uuid",
  "lat": 40.4168,
  "lng": -3.7038
}

// Response 200 — OK
{
  "data": {
    "id": "uuid",
    "employee": { "id": "uuid", "name": "Juan García" },
    "milestone": { "name": "ENTRADA", "type": "entrada" },
    "clocked_at": "2026-06-11T09:00:00Z"
  }
}

// Response 409 — Doble entrada sin salida previa
{
  "message": "Ya existe un fichaje de entrada sin salida.",
  "code": "DOUBLE_ENTRY",
  "last_attendance": { "clocked_at": "...", "milestone": "ENTRADA" }
}
```

**PUT /attendance/{id}** — Corrección
```json
// Request
{ "new_clocked_at": "2026-06-11T09:05:00Z", "reason": "Error del empleado" }
```

---

## Ausencias y presencias

```
GET    /leave-requests              Query: employee_id, status, date_from, date_to
POST   /leave-requests              Solicitar (empleado)
GET    /leave-requests/{id}
DELETE /leave-requests/{id}         Solo si status=pendiente
POST   /leave-requests/{id}/approve Aprobar (coordinator/admin)
POST   /leave-requests/{id}/reject  Rechazar con nota

GET    /employees/{id}/vacations    Resumen vacaciones año (disponibles/solicitadas/aprobadas/restantes)

GET    /leave-requests/pending      Pendientes de aprobación del coordinador autenticado
```

---

## Organigrama

```
GET    /org-chart/{work_center_id}  Árbol completo del centro
POST   /org-chart/nodes             Añadir nodo
PUT    /org-chart/nodes/{id}        Mover nodo (parent_id, sort_order)
DELETE /org-chart/nodes/{id}
PATCH  /org-chart/nodes/{id}/notifications  Toggle recibe_notificaciones
```

---

## Informes

```
POST   /reports/daily-attendance    Informe diario (PDF/JSON)
POST   /reports/work-time-record    Registro horario ET 34.9 (Excel/PDF)
POST   /reports/leave-summary       Resumen ausencias (Excel)
```

**POST /reports/work-time-record**
```json
// Request
{
  "date_from": "2026-01-01",
  "date_to": "2026-06-30",
  "company_id": "uuid",
  "work_center_ids": ["uuid"],
  "employee_ids": ["uuid"],          // vacío = todos
  "format": "excel",                 // "excel" | "pdf"
  "options": {
    "include_work_center": true,
    "include_delays": true,
    "include_geolocation": false,
    "include_method": false,
    "decimal_format": false,
    "split_by_employee": false,       // true = ZIP con un fichero por empleado
    "password": "opcional"
  }
}
// Response 200
{ "data": { "download_url": "https://...", "expires_at": "..." } }
```

---

## Módulo Socios

```
GET    /entities                    Lista entidades del tenant
POST   /entities
GET    /entities/{id}
PUT    /entities/{id}
DELETE /entities/{id}

GET    /entities/{id}/member-types
POST   /entities/{id}/member-types
PUT    /member-types/{id}
DELETE /member-types/{id}

GET    /entities/{id}/members       Filtros: status, member_type_id, search
POST   /entities/{id}/members
POST   /entities/{id}/members/import  Excel
GET    /entities/{id}/members/template  Plantilla Excel
GET    /members/{id}
PUT    /members/{id}
PATCH  /members/{id}/status

GET    /members/{id}/payments
POST   /members/{id}/payments
PUT    /member-payments/{id}
DELETE /member-payments/{id}

GET    /entities/{id}/expenses      Filtros: category_id, date_from, date_to
POST   /entities/{id}/expenses
PUT    /expenses/{id}
DELETE /expenses/{id}

GET    /entities/{id}/expense-categories
POST   /entities/{id}/expense-categories
PUT    /expense-categories/{id}
DELETE /expense-categories/{id}

GET    /entities/{id}/treasury      Tesorería del ejercicio activo
GET    /entities/{id}/treasury/{year}

GET    /entities/{id}/export        Export ZIP: socios.xlsx + pagos.xlsx + gastos.xlsx
GET    /entities/{id}/export/json   Backup completo JSON
POST   /entities/{id}/import/json   Restaurar desde backup

GET    /entities/{id}/events
POST   /entities/{id}/events
PUT    /events/{id}
DELETE /events/{id}
GET    /events/{id}/attendances
POST   /events/{id}/attendances
```

**GET /entities/{id}/treasury**
```json
{
  "data": {
    "year": 2026,
    "opening_balance": 1500.00,
    "total_income": 4250.00,
    "total_expenses": 1820.00,
    "current_balance": 3930.00,
    "pending_payments": 850.00,       // socios con cuota pendiente
    "collected_this_year": 4250.00
  }
}
```

---

## Contabilidad

```
GET    /accounts                    Plan de cuentas
POST   /accounts
PUT    /accounts/{id}

GET    /journal-entries             Filtros: date_from, date_to, status
POST   /journal-entries
GET    /journal-entries/{id}
PUT    /journal-entries/{id}        Solo si status=borrador
POST   /journal-entries/{id}/confirm

GET    /invoices                    Filtros: type, date_from, date_to
POST   /invoices
GET    /invoices/{id}
PUT    /invoices/{id}

GET    /payment-dues                Filtros: type, status, due_date_from
POST   /payment-dues
PUT    /payment-dues/{id}
PATCH  /payment-dues/{id}/settle    Marcar como cobrado/pagado

POST   /suenlace/export             Genera fichero suenlace.dat
GET    /suenlace/exports            Historial de exportaciones
GET    /suenlace/exports/{id}/download
```

**POST /suenlace/export**
```json
// Request
{
  "year": 2026,
  "type": "completo",    // "gastos" | "facturas" | "nominas" | "completo"
  "entity_id": "uuid"    // null = empresa (RRHH)
}
// Response 200
{
  "data": {
    "id": "uuid",
    "records_count": 142,
    "download_url": "https://...",
    "expires_at": "2026-06-12T17:00:00Z"
  }
}
```

---

## Portal del empleado (rutas prefijo `/me`)

```
GET    /me                          Perfil + rol + empresa
PUT    /me                          Actualizar datos personales
GET    /me/attendances              Mis fichajes (paginado, filtro semana/mes)
GET    /me/attendances/report       PDF de mis fichajes (rango de fechas)
GET    /me/schedule                 Mi horario (calendario anual)
GET    /me/leave-requests           Mis solicitudes
POST   /me/leave-requests           Nueva solicitud
DELETE /me/leave-requests/{id}      Cancelar solicitud pendiente
GET    /me/vacations                Mis vacaciones año actual
GET    /me/payslips                 Mis nóminas
GET    /me/payslips/{id}/download   Descargar PDF
GET    /me/documents                Mis documentos
GET    /me/documents/{id}/download
GET    /me/advances                 Mis adelantos
POST   /me/expenses                 Imputar gasto
GET    /me/expenses
POST   /me/improvement-proposals    Solicitar mejora
GET    /me/improvement-proposals
POST   /me/material-requests        Reservar material
GET    /me/delays                   Mis retrasos
POST   /me/delays/{id}/justify      Justificar retraso
GET    /me/news                     Noticias corporativas no leídas
POST   /me/news/{id}/read           Marcar como leída
```

---

## Portal del socio (rutas prefijo `/member-portal`)

```
GET    /member-portal/me            Perfil del socio
GET    /member-portal/payments      Mis cuotas y estado
GET    /member-portal/payments/{id}/receipt  PDF del recibo
GET    /member-portal/events        Próximos eventos
POST   /member-portal/events/{id}/attend    Inscribirse
DELETE /member-portal/events/{id}/attend    Cancelar inscripción
```

---

## Códigos de error propios

| Código | Descripción |
|---|---|
| `DOUBLE_ENTRY` | Fichaje de entrada sin salida previa |
| `INVALID_CLOCK_CODE` | Código PIN no encontrado |
| `IP_NOT_ALLOWED` | La IP no está permitida para este empleado |
| `TENANT_SUSPENDED` | Tenant suspendido (impago) |
| `PLAN_LIMIT_REACHED` | Límite de empleados/socios del plan actual |
| `LEAVE_OVERLAP` | La ausencia solicitada se solapa con otra |
| `SUENLACE_EXPORT_EMPTY` | No hay datos para el período/tipo solicitado |
| `CORRECTION_IMMUTABLE` | No se puede modificar una corrección ya creada |
