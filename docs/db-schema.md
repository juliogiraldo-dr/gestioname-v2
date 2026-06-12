# Esquema de base de datos — Gestioname v2

> PostgreSQL 16. Multi-tenant con schemas separados.
> Schema `public`: tablas del sistema. Schema `{tenant}`: tablas de negocio.

---

## Schema PUBLIC — Sistema

```sql
-- Tenants registrados
tenants
  id               uuid PK
  name             varchar(255)
  subdomain        varchar(100) UNIQUE
  custom_domain    varchar(255) UNIQUE NULLABLE  -- marca blanca: dominio propio (CNAME → tenant)
  plan             enum('free','essential','professional','business','enterprise')
  status           enum('active','suspended','cancelled')
  trial_ends_at    timestamp
  created_at       timestamp
  updated_at       timestamp

-- Personalización visual (marca blanca), schema public. La sirve GET /api/v1/branding (público).
tenant_branding
  id               uuid PK
  tenant_id        uuid FK → tenants.id UNIQUE
  logo_path        varchar
  primary_color    varchar(7)    -- #RRGGBB
  app_name         varchar(255)

-- Facturación (Stripe)
tenant_subscriptions
  id               uuid PK
  tenant_id        uuid FK → tenants.id
  stripe_customer_id   varchar
  stripe_subscription_id varchar
  plan             varchar
  status           varchar
  current_period_start timestamp
  current_period_end   timestamp
  created_at       timestamp
```

---

## Schema TENANT — RRHH y Control de Jornada

### Empresas y estructura

```sql
company_groups
  id                   uuid PK
  name                 varchar(255)        -- grupo de empresas (p. ej. "Grupo Datarecover")
  created_at           timestamp
  updated_at           timestamp

-- Módulos activables a nivel de TENANT (no por empresa): un cliente puede ser empresa,
-- entidad/asociación o ambas, con módulos independientes.
tenant_modules
  id    uuid PK
  key   varchar(50) UNIQUE   -- rrhh | socios | tesoreria | nominas | comunicaciones
  enabled boolean DEFAULT false

companies
  id                   uuid PK
  company_group_id     uuid FK → company_groups.id NULLABLE  -- grupo al que pertenece
  name                 varchar(255)
  cif                  varchar(20)
  address              text
  phone                varchar(20)
  email                varchar(255)
  logo_path            varchar
  created_at           timestamp
  updated_at           timestamp

work_centers
  id               uuid PK
  company_id       uuid FK → companies.id
  name             varchar(255)
  address          text
  lat              decimal(10,8)
  lng              decimal(11,8)
  timezone         varchar(50)  -- 'Europe/Madrid'
  created_at       timestamp

-- Hitos personalizables (Entrada, Salida, Salida a comer...)
attendance_milestones
  id               uuid PK
  company_id       uuid FK → companies.id
  name             varchar(100)
  description      text
  color            varchar(7)   -- hex '#90cbe8'
  type             enum('entrada','salida')
  show_in_report   boolean DEFAULT true
  active           boolean DEFAULT true
  created_at       timestamp

-- Relación hito ↔ centros de trabajo
milestone_work_centers
  milestone_id     uuid FK → attendance_milestones.id
  work_center_id   uuid FK → work_centers.id
  PRIMARY KEY (milestone_id, work_center_id)

holidays
  id               uuid PK
  name             varchar(255)
  type             enum('nacional','autonomico','local')
  repeatable       boolean       -- true = mismo día cada año
  day_of_year      smallint      -- si repeatable: día del año (1-366)
  date             date          -- si no repeatable: fecha exacta
  province         varchar(100)
  locality         varchar(100)
  created_at       timestamp

holiday_work_centers
  holiday_id       uuid FK → holidays.id
  work_center_id   uuid FK → work_centers.id
  PRIMARY KEY (holiday_id, work_center_id)
```

### Convenios y calendarios

```sql
agreements
  id               uuid PK
  company_id       uuid FK → companies.id
  name             varchar(255)
  annual_hours     decimal(6,2)
  vacation_days    smallint
  vacation_type    enum('laborables','naturales')
  vacation_expiry  date           -- fecha límite disfrute vacaciones
  exercise_close   date           -- cierre del ejercicio
  created_at       timestamp

agreement_leave_types
  id               uuid PK
  agreement_id     uuid FK → agreements.id
  name             varchar(100)
  type             enum('ausencia','presencia')
  count_in         enum('dias','horas')
  subtracts_vacation boolean
  requires_document boolean
  visible_to_employee boolean
  max_days         smallint
  max_hours        decimal(5,2)

-- Plantillas de horario reutilizables
schedule_templates
  id               uuid PK
  company_id       uuid FK → companies.id
  name             varchar(100)
  color            varchar(7)
  type             enum('fijo','flexible','libre')
  year             smallint
  tolerance_minutes smallint DEFAULT 0   -- retraso permitido (tipo fijo)
  -- flexible: rango horario + horas a cumplir
  flex_start_min   time
  flex_start_max   time
  flex_hours_day   decimal(4,2)
  -- libre: horas totales
  free_hours_daily   decimal(4,2)
  free_hours_weekly  decimal(5,2)
  free_hours_monthly decimal(6,2)
  free_hours_annual  decimal(7,2)
  active           boolean DEFAULT true
  created_at       timestamp

-- Tramos horarios para tipo FIJO
schedule_time_ranges
  id               uuid PK
  schedule_template_id uuid FK → schedule_templates.id
  time_start       time
  time_end         time
  sort_order       smallint

work_calendars
  id               uuid PK
  company_id       uuid FK → companies.id
  name             varchar(100)
  color            varchar(7)
  year             smallint
  country          varchar(3)   -- 'ESP'
  province         varchar(100)
  locality         varchar(100)
  description      text
  created_at       timestamp

-- Asignación de horario a cada día del calendario
calendar_days
  id               uuid PK
  calendar_id      uuid FK → work_calendars.id
  date             date
  schedule_template_id uuid FK → schedule_templates.id
  INDEX (calendar_id, date)

-- Empleados asignados a un calendario
calendar_employees
  calendar_id      uuid FK → work_calendars.id
  employee_id      uuid FK → employees.id
  PRIMARY KEY (calendar_id, employee_id)
```

### Empleados

```sql
employees
  id               uuid PK
  company_id       uuid FK → companies.id
  work_center_id   uuid FK → work_centers.id
  agreement_id     uuid FK → agreements.id
  user_id          uuid FK → users.id  -- acceso portal
  -- Datos básicos
  first_name       varchar(100)
  last_name        varchar(100)
  second_last_name varchar(100)
  treatment        enum('sr','sra','dr','dra')
  dni              varchar(15) ENCRYPTED
  birth_date       date
  birth_place      varchar(100)
  nationality      varchar(50)
  -- Fichaje
  clock_code       char(8) UNIQUE     -- PIN de 8 dígitos
  exempt_from_clock boolean DEFAULT false
  -- Empresa
  department       varchar(100)
  job_position     varchar(100)
  job_category     varchar(100)
  employment_status enum('active','inactive','leave')
  hire_date        date
  -- Contacto
  email_company    varchar(255)
  phone_company    varchar(20)
  mobile_company   varchar(20)
  -- Personal
  email_personal   varchar(255)
  phone_personal   varchar(20)
  address          text
  postal_code      varchar(10)
  city             varchar(100)
  province         varchar(100)
  -- Bancario
  iban             varchar(34) ENCRYPTED
  -- Vehículo
  vehicle_plate    varchar(15)
  -- Meta
  photo_path       varchar
  notes            text
  active           boolean DEFAULT true
  created_at       timestamp
  updated_at       timestamp
  INDEX (company_id, active)
  INDEX (clock_code)

employee_allowed_ips
  id               uuid PK
  employee_id      uuid FK → employees.id
  ip_address       varchar(45)
  description      varchar(100)

employee_family_contacts
  id               uuid PK
  employee_id      uuid FK → employees.id
  name             varchar(200)
  relationship     varchar(50)
  phone            varchar(20)
  email            varchar(255)

-- Contratos de trabajo
employee_contracts
  id               uuid PK
  employee_id      uuid FK → employees.id
  type             varchar(100)   -- 'indefinido', 'temporal', 'formación'...
  date_start       date
  date_end         date
  working_hours    decimal(4,2)   -- horas semanales
  document_path    varchar
  notes            text
  created_at       timestamp

employee_salaries
  id               uuid PK
  employee_id      uuid FK → employees.id
  gross_amount     decimal(10,2)
  net_amount       decimal(10,2)
  effective_date   date
  notes            text
  created_at       timestamp

employee_social_benefits
  id               uuid PK
  employee_id      uuid FK → employees.id
  type             varchar(100)
  amount           decimal(10,2)
  description      text
  created_at       timestamp

-- Formación
employee_qualifications
  id               uuid PK
  employee_id      uuid FK → employees.id
  type             enum('titulacion','curso','certificado','conocimiento','experiencia')
  name             varchar(255)
  institution      varchar(255)
  date_obtained    date
  expiry_date      date
  document_path    varchar
  notes            text

-- Comportamiento
employee_behavior_records
  id               uuid PK
  employee_id      uuid FK → employees.id
  type             enum('felicitacion','amonestacion','sancion')
  date             date
  description      text
  document_path    varchar
  created_by       uuid FK → users.id
  created_at       timestamp

-- Materiales cedidos
employee_materials
  id               uuid PK
  employee_id      uuid FK → employees.id
  name             varchar(255)
  serial_number    varchar(100)
  delivery_date    date
  return_date      date
  status           enum('entregado','devuelto','perdido')
  notes            text

-- Adelantos
employee_advances
  id               uuid PK
  employee_id      uuid FK → employees.id
  amount           decimal(10,2)
  date             date
  description      text
  status           enum('pendiente','descontado')

-- Gastos del empleado
employee_expenses
  id               uuid PK
  employee_id      uuid FK → employees.id
  amount           decimal(10,2)
  date             date
  category         varchar(100)
  description      text
  receipt_path     varchar
  status           enum('pendiente','aprobado','rechazado','pagado')
  approved_by      uuid FK → users.id
  created_at       timestamp

-- Propuestas de mejora
employee_improvement_proposals
  id               uuid PK
  employee_id      uuid FK → employees.id
  title            varchar(255)
  description      text
  status           enum('enviada','en_revision','aceptada','rechazada')
  response         text
  created_at       timestamp

-- Documentos del empleado
employee_documents
  id               uuid PK
  employee_id      uuid FK → employees.id
  name             varchar(255)
  type             varchar(100)  -- 'nomina', 'contrato', 'certificado', 'otro'
  file_path        varchar
  visible_to_employee boolean DEFAULT true
  created_at       timestamp
```

### Fichajes y registro de jornada

```sql
-- Registro principal de fichajes (INMUTABLE — ET 34.9)
attendances
  id               uuid PK
  employee_id      uuid FK → employees.id
  milestone_id     uuid FK → attendance_milestones.id
  clocked_at       timestamp     -- marca de tiempo original
  lat              decimal(10,8)
  lng              decimal(11,8)
  ip_address       varchar(45)
  method           enum('web','movil','kiosk','manual')
  created_at       timestamp
  INDEX (employee_id, clocked_at)
  INDEX (DATE(clocked_at))

-- Correcciones manuales — trazabilidad obligatoria ET 34.9
attendance_corrections
  id               uuid PK
  attendance_id    uuid FK → attendances.id
  corrected_by     uuid FK → users.id
  old_clocked_at   timestamp
  new_clocked_at   timestamp
  reason           text NOT NULL
  created_at       timestamp     -- inmutable, nunca se borra
```

### Ausencias, presencias y organigrama

```sql
-- Organigrama
org_chart_nodes
  id               uuid PK
  work_center_id   uuid FK → work_centers.id
  employee_id      uuid FK → employees.id
  parent_id        uuid FK → org_chart_nodes.id NULLABLE
  receives_notifications boolean DEFAULT false
  sort_order       smallint
  INDEX (work_center_id)

-- Solicitudes de ausencia/presencia
leave_requests
  id               uuid PK
  employee_id      uuid FK → employees.id
  leave_type_id    uuid FK → agreement_leave_types.id
  date_start       date
  date_end         date
  time_start       time    -- si es por horas
  time_end         time
  total_days       decimal(4,1)
  total_hours      decimal(5,2)
  description      text
  document_path    varchar
  status           enum('pendiente','aprobada','rechazada','cancelada')
  reviewed_by      uuid FK → users.id
  reviewed_at      timestamp
  review_note      text
  created_at       timestamp
  INDEX (employee_id, date_start)
  INDEX (status)

-- Retrasos
attendance_delays
  id               uuid PK
  attendance_id    uuid FK → attendances.id
  employee_id      uuid FK → employees.id
  delay_minutes    smallint
  justified        boolean DEFAULT false
  justification    text
  document_path    varchar
  reviewed_by      uuid FK → users.id
  created_at       timestamp
```

### Noticias corporativas

```sql
company_news
  id               uuid PK
  company_id       uuid FK → companies.id
  title            varchar(255)
  body             text
  published_at     timestamp
  author_id        uuid FK → users.id
  created_at       timestamp

-- Lectura por empleado
news_reads
  news_id          uuid FK → company_news.id
  employee_id      uuid FK → employees.id
  read_at          timestamp
  PRIMARY KEY (news_id, employee_id)
```

---

## Schema TENANT — Módulo Asociaciones

```sql
-- Entidad/asociación (puede haber varias por tenant)
-- Entidad/asociación INDEPENDIENTE a nivel de tenant (no pertenece a una empresa).
entities
  id               uuid PK
  name             varchar(255)
  type             enum('pena','ampa','asociacion_cultural','vecinal','club','cofradia','otro')
  cif              varchar(20)
  address          text
  phone            varchar(20)
  email            varchar(255)
  logo_path        varchar
  opening_balance  decimal(12,2) DEFAULT 0  -- saldo inicial del ejercicio
  fiscal_year      smallint      -- año del ejercicio activo
  created_at       timestamp

-- Tipos de socio con cuotas
member_types
  id               uuid PK
  entity_id        uuid FK → entities.id
  name             varchar(100)  -- 'Adulto', 'Joven 16-17', 'Infantil', 'Honor'
  description      text
  fee_amount       decimal(10,2)
  fee_periodicity  enum('anual','semestral','trimestral','mensual')
  active           boolean DEFAULT true

-- Socios
members
  id               uuid PK
  entity_id        uuid FK → entities.id
  member_type_id   uuid FK → member_types.id
  member_number    varchar(20)    -- número de socio
  first_name       varchar(100)
  last_name        varchar(200)
  dni              varchar(15)
  birth_date       date
  address          text
  postal_code      varchar(10)
  city             varchar(100)
  phone            varchar(20)
  email            varchar(255)
  date_join        date
  date_leave       date
  status           enum('activo','baja_voluntaria','baja_impagada','honor','pendiente')
  user_id          uuid FK → users.id  -- portal del socio
  notes            text
  created_at       timestamp
  updated_at       timestamp
  INDEX (entity_id, status)
  INDEX (entity_id, member_type_id)

-- Pagos de cuota
member_payments
  id               uuid PK
  member_id        uuid FK → members.id
  entity_id        uuid FK → entities.id
  year             smallint
  amount           decimal(10,2)
  status           enum('pagado','parcial','pendiente')
  payment_date     date
  payment_method   enum('efectivo','transferencia','bizum','domiciliacion','otro')
  reference        varchar(100)
  notes            text
  created_by       uuid FK → users.id
  created_at       timestamp
  INDEX (entity_id, year, status)

-- Categorías de gasto
expense_categories
  id               uuid PK
  entity_id        uuid FK → entities.id
  name             varchar(100)  -- 'Alquiler local', 'Material', 'Actos', 'Seguros'
  color            varchar(7)

-- Gastos de la entidad
expenses
  id               uuid PK
  entity_id        uuid FK → entities.id
  category_id      uuid FK → expense_categories.id
  amount           decimal(10,2)
  date             date
  description      varchar(255)
  notes            text
  receipt_path     varchar
  created_by       uuid FK → users.id
  created_at       timestamp
  INDEX (entity_id, date)

-- Eventos
events
  id               uuid PK
  entity_id        uuid FK → entities.id
  title            varchar(255)
  description      text
  date_start       timestamp
  date_end         timestamp
  location         varchar(255)
  capacity         smallint
  price            decimal(8,2) DEFAULT 0
  public           boolean DEFAULT true
  created_at       timestamp

-- Asistencia a eventos
event_attendances
  id               uuid PK
  event_id         uuid FK → events.id
  member_id        uuid FK → members.id
  status           enum('inscrito','asistio','no_asistio','cancelado')
  payment_status   enum('gratuito','pagado','pendiente')
  created_at       timestamp
  UNIQUE (event_id, member_id)
```

---

## Schema TENANT — Contabilidad Básica

```sql
-- Plan de cuentas (simplificado PGC grupos 1-7)
accounts
  id               uuid PK
  entity_id        uuid FK → entities.id NULLABLE  -- null = empresa
  code             varchar(12)   -- '57000000' (nivel 6-12)
  name             varchar(255)
  type             enum('activo','pasivo','patrimonio','ingreso','gasto')
  group_num        smallint      -- 1-7
  parent_code      varchar(12)
  active           boolean DEFAULT true
  INDEX (entity_id, code)

-- Asientos contables
journal_entries
  id               uuid PK
  entity_id        uuid FK → entities.id NULLABLE
  date             date
  description      varchar(255)
  reference        varchar(100)
  status           enum('borrador','confirmado')
  created_by       uuid FK → users.id
  created_at       timestamp
  INDEX (entity_id, date)

-- Líneas de asiento
journal_lines
  id               uuid PK
  entry_id         uuid FK → journal_entries.id
  account_id       uuid FK → accounts.id
  debit            decimal(14,2) DEFAULT 0
  credit           decimal(14,2) DEFAULT 0
  description      varchar(255)
  sort_order       smallint
  CONSTRAINT check_debit_credit CHECK (debit >= 0 AND credit >= 0)

-- Facturas emitidas/recibidas
invoices
  id               uuid PK
  entity_id        uuid FK → entities.id NULLABLE
  type             enum('emitida','recibida')
  number           varchar(60)
  date             date
  operation_date   date
  counterpart_name varchar(255)
  counterpart_nif  varchar(20)
  counterpart_cp   varchar(10)
  total_base       decimal(14,2)
  total_vat        decimal(14,2)
  total_amount     decimal(14,2)
  journal_entry_id uuid FK → journal_entries.id
  created_at       timestamp

-- Líneas de IVA de factura
invoice_vat_lines
  id               uuid PK
  invoice_id       uuid FK → invoices.id
  subtype          varchar(2)    -- '01'-'09' según suenlace spec
  base_amount      decimal(14,2)
  vat_rate         decimal(5,2)
  vat_amount       decimal(14,2)
  surcharge_rate   decimal(5,2)
  surcharge_amount decimal(14,2)
  retention_rate   decimal(5,2)
  retention_amount decimal(14,2)

-- Vencimientos
payment_dues
  id               uuid PK
  entity_id        uuid FK → entities.id NULLABLE
  invoice_id       uuid FK → invoices.id NULLABLE
  type             enum('cobro','pago')
  account_id       uuid FK → accounts.id
  due_date         date
  invoice_date     date
  amount           decimal(14,2)
  treasury_account_id uuid FK → accounts.id NULLABLE
  payment_method   varchar(2)    -- '01'-'99'
  status           enum('pendiente','cobrado','pagado','devuelto')
  payment_date     date
  created_at       timestamp

-- Exportaciones suenlace.dat generadas
suenlace_exports
  id               uuid PK
  entity_id        uuid FK → entities.id NULLABLE
  year             smallint
  type             enum('gastos','facturas','nominas','completo')
  file_path        varchar
  records_count    integer
  generated_by     uuid FK → users.id
  generated_at     timestamp
```

---

## Tablas de auditoría (todas las schemas tenant)

```sql
-- Log de acciones críticas
audit_logs
  id               bigserial PK
  user_id          uuid
  action           varchar(100)  -- 'attendance.correction', 'employee.delete', etc.
  model_type       varchar(100)
  model_id         uuid
  old_values       jsonb
  new_values       jsonb
  ip_address       varchar(45)
  user_agent       varchar(255)
  created_at       timestamp
  INDEX (model_type, model_id)
  INDEX (user_id, created_at)
  -- NUNCA borrar registros de esta tabla
```

---

## Notas de implementación

- Todos los `id` son UUID v4 generados en aplicación.
- Campos `ENCRYPTED`: usar `encrypt()`/`decrypt()` de Laravel Crypt antes de guardar.
- Campos `timestamp` siempre en UTC. Conversión a zona horaria del centro en frontend.
- `INDEX` indicados son los mínimos requeridos. Añadir según `EXPLAIN ANALYZE` en producción.
- Migraciones en `/backend/database/migrations/` siguiendo convención `YYYY_MM_DD_HHMMSS_{descripcion}.php`.
- Seeders en `/backend/database/seeders/` con factorías en `database/factories/`.
