# Roadmap — Gestioname v2

> Actualiza este archivo al inicio y fin de cada sprint.
> Usa `[ ]` para pendiente, `[x]` para completado, `[~]` para en progreso.

---

## Fase 0 · Preparación (2 semanas)

### Sprint 0 — Infraestructura base
- [ ] Crear repositorio Git con estructura monorepo (`/backend`, `/frontend`, `/docker`, `/docs`, `/scripts`)
- [ ] `.gitignore`, `.editorconfig`, `README.md` raíz
- [ ] `docker-compose.yml` con servicios: php-fpm, nginx, postgres, redis, mailpit
- [ ] `Makefile` con targets: `up`, `down`, `bash`, `migrate`, `test`, `lint`
- [ ] Variables de entorno: `.env.example` documentado campo a campo
- [ ] GitHub Actions: pipeline CI (lint + tests en cada PR)
- [ ] GitHub Actions: pipeline CD (deploy a staging en merge a `main`)
- [ ] Traefik configurado en deploy.datarecover.cloud con Let's Encrypt
- [ ] Dominio `staging.gestioname.app` apuntando al servidor
- [ ] Scaffolding Laravel 11 en `/backend` con Sanctum, Spatie Permission, DomPDF, PhpSpreadsheet
- [ ] Scaffolding Next.js 14 en `/frontend` con TypeScript, Tailwind, App Router
- [ ] Primer deploy funcional (homepage Laravel en staging)

---

## Fase 1 · MVP RRHH + Socios (meses 1–6)

### Sprint 1 — Multi-tenancy y autenticación (semanas 1-2)
- [x] `TenantMiddleware`: identifica tenant por subdominio, carga schema PostgreSQL correcto
- [x] Comando Artisan `tenant:create {name} {subdomain}`: crea schema + ejecuta migraciones
- [x] Comando Artisan `migrate:tenants`: migra todos los schemas (secuencial e idempotente; cada schema con su propia tabla `migrations`)
- [x] Tabla `tenants` en schema `public` (id, name, subdomain, plan, status, created_at)
- [x] Tabla `users` en schema tenant con campos: name, email, dni (cifrado), phone, code_fichaje (8 dígitos), avatar
- [x] Spatie Permission: roles `super-admin`, `admin`, `rrhh-coordinator`, `operator`, `employee`, `member`
- [x] Auth API: POST `/auth/login` (email + password), respuesta con Sanctum token
- [x] Auth API: POST `/auth/magic-link` (email) — envía enlace de acceso por email
- [x] Auth API: POST `/auth/magic-link/verify` — valida token, devuelve Sanctum token
- [x] Auth API: POST `/auth/logout`
- [x] Auth API: POST `/auth/refresh`
- [x] Rate limiting: 5 intentos de login por minuto por IP
- [x] Seeder demo: tenant `demo`, empresa "Datarecover Demo", usuario `admin@demo.gestioname.app`
- [x] Tests: `TenantMiddlewareTest`, `AuthApiTest` (+ `TenantCreateCommandTest`) — 28 tests verdes

### Sprint 2 — Configuración de empresa (semanas 3-4)
- [x] CRUD Empresas (`companies`): name, cif, address, phone, email, logo_path. Borrado bloqueado si hay empleados (guard preparado para Sprint 4)
- [x] CRUD Centros de trabajo (`work_centers`): name, company_id, address, lat, lng, timezone. Anidados bajo empresa para listar/crear
- [x] CRUD Festivos (`holidays`): name, type (nacional/autonomico/local), repeatable+day_of_year ó date, province, locality, work_centers[]
- [x] Seed festivos nacionales España 2025-2026 precargados (`NationalHolidaySeeder`, cableado en `TenantProvisioner`)
- [x] CRUD Hitos de fichaje (`attendance_milestones`): name, description, color, type (entrada/salida), show_in_report, active, company_id, centros[]
- [x] Hitos por defecto: ENTRADA, SALIDA (se crean al dar de alta cada empresa)
- [x] CRUD Convenios (`agreements`): company_id, name, annual_hours, vacation_days, vacation_type (laborables/naturales), vacation_expiry, exercise_close. Borrado bloqueado si hay empleados (guard para Sprint 4)
- [x] CRUD Tipos de ausencia/presencia por convenio (`agreement_leave_types`): name, type (ausencia/presencia), count_in (dias/horas), subtracts_vacation, requires_document, visible_to_employee, max_days, max_hours. Anidados bajo convenio para listar/crear
- [x] Tests: `CompanyApiTest`, `WorkCenterApiTest`, `HolidayApiTest` (+ `MilestoneApiTest`, `AgreementApiTest`, `AgreementLeaveTypeApiTest`) — 65 tests verdes en total

### Sprint 3 — Calendarios laborales (semanas 5-6) ✅
- [x] CRUD Plantillas de horario (`schedule_templates`): name, color, type (fijo/flexible/libre), tolerance_minutes, año
  - [x] Fijo: tramos horarios (`schedule_time_ranges`) + cálculo de horas/día
  - [x] Flexible: flex_start_min/max, flex_hours_day
  - [x] Libre: horas_diarias / semanales / mensuales / anuales
- [x] CRUD Calendarios anuales (`work_calendars`): name, color, year, country, province, locality, description
- [x] Asignación días: `calendar_days` (calendar_id, date, schedule_template_id)
- [x] Endpoint POST `/calendars/{id}/fill-quick`: llenado rápido (weekdays[], months[], schedule_template_id, include_holidays)
- [x] Endpoint POST `/calendars/{id}/fill-manual`: asigna horario a array de fechas
- [x] Endpoint DELETE `/calendars/{id}/clear`: borrado por rango / fechas / todo
- [x] Endpoint POST `/calendars/{id}/clone`: clona a otro año
- [x] Endpoint GET `/calendars/{id}/simulate-vacation`: simulación de horas en un rango
- [x] Asignación de empleados al calendario (`calendar_employees`, POST /calendars/{id}/employees)
- [x] Tests: `CalendarApiTest`, `QuickFillTest`, `ScheduleTemplateApiTest`

### Sprint 4 — Empleados (semanas 7-8) ✅
- [x] Ficha empleada completa (`employees`): campos de db-schema (dni/iban cifrados, clock_code 8 dígitos)
- [x] Alta manual, por invitación email (magic link) e importación Excel (+ plantilla descargable, PhpSpreadsheet)
- [x] Endpoint GET `/employees` con filtros: empresa, centro, departamento, estado, convenio, calendario, activo
- [x] Incidencias automáticas: sin centro, sin convenio, sin calendario año actual, horas insuficientes/excedidas
- [x] Índice Bradford: `BradfordIndexCalculator` + endpoint `/employees/{id}/bradford`
- [x] Control IP por empleado (`employee_allowed_ips`)
- [~] Sub-módulos Formación / Relación empresa / Comportamiento / Materiales: **tablas y migraciones creadas** (esquema completo); CRUD de endpoints pendiente (siguen el mismo patrón anidado)
- [x] Activación/desactivación de empleados
- [x] Tests: `EmployeeApiTest`, `EmployeeImportTest`, `BradfordIndexTest`

### Sprint 5 — Fichajes (semanas 9-10) ✅
- [x] Endpoint POST `/attendance/clock` (clock_code, milestone_id, lat?, lng?, ip) — público en el tenant
- [x] Validaciones: doble entrada (409 DOUBLE_ENTRY), salida sin entrada (409 NO_OPEN_ENTRY), código inválido, IP no permitida
- [x] Tabla `attendances`: employee_id, milestone_id, clocked_at, lat, lng, ip, method, soft delete
- [x] Corrección manual: `attendance_corrections` (old/new_clocked_at, corrected_by, reason) — inmutable ET 34.9
- [x] Endpoint GET `/attendance/daily` — informe diario por empresa/centro
- [x] Endpoint POST `/attendance/manual` — fichaje manual
- [x] Endpoint PUT/DELETE `/attendance/{id}` — corregir/borrar con auditoría (borrado lógico)
- [x] Tests: `AttendanceClockTest`, `AttendanceCorrectionAuditTest`

### Sprint 6 — Ausencias, presencias y organigrama (semanas 11-12) ✅
- [x] CRUD Organigrama (`org_chart_nodes`): employee_id, parent_id, work_center_id, receives_notifications + árbol
- [x] Workflow ausencias: solicitud → notificación a gestores → aprobación/rechazo → notificación al empleado
- [x] Tabla `leave_requests` + cálculo automático de total_days/total_hours y control de solapamiento (409 LEAVE_OVERLAP)
- [x] Endpoint POST `/leave-requests/{id}/approve`
- [x] Endpoint POST `/leave-requests/{id}/reject`
- [x] Listado/filtros de ausencias + pendientes del gestor (la "cuadrícula" visual es del frontend)
- [x] Cálculo de vacaciones disponibles/solicitadas/aprobadas/restantes (`/employees/{id}/vacations`)
- [x] Tests: `LeaveRequestWorkflowTest`, `OrgChartTest`

### Sprint 7 — Portal del empleado (semanas 13-14) ✅ (núcleo)
- [x] Backend del portal: endpoints `/api/v1/me` (perfil, fichajes, horario, tipos/solicitudes de ausencia, vacaciones) + `MePortalTest`
- [x] Sistema de diseño Fluent (tokens Tailwind 4: #0F2756 / #5EB8D0 / #68DFB9, sombras sutiles, iconos de línea fina)
- [x] Next.js: login + layout portal (sidebar + header con nombre y rol) con guard de auth
- [x] Página Inicio: KPIs (vacaciones, días aprobados, solicitudes pendientes, puesto)
- [x] Página Mis Fichajes: listado (generador PDF pendiente)
- [x] Página Mi Horario: calendario visual con colores por horario
- [x] Página Solicitar Ausencia: formulario (tipo + fechas) + listado con estado
- [~] Páginas Mis Nóminas / Documentos / Noticias: maquetadas (stubs); Presencia / Retrasos / Adelantos / Gastos / Mejoras / Material pendientes
- [x] Reloj de fichar kiosk: PIN → ENTRADA/SALIDA → confirmación / alerta
- [~] Tests E2E (Playwright): config + spec creados (`e2e/portal.spec.ts`); requieren `npx playwright install` + ambos servidores para ejecutarse
- [x] `next build` verde (TypeScript OK, 11 rutas)

### Sprint 8 — Informes registro horario (semanas 15-16) 🚧
- [x] Informe diario: backend `POST /reports/daily-attendance` (JSON + PDF) + barra visual por empleado (06:00–22:00) en `/admin/informes`
- [x] Informe registro horario (ET 34.9): Excel + PDF — `POST /reports/work-time-record`
  - [x] Filtros: rango fechas, empresa, centros, empleados (departamento se filtra vía empleados)
  - [x] Opciones: incluir centro, retrasos, geolocalización, método fichaje, formato decimal
  - [x] Totales: horas previstas, sobretiempo, realizadas, retrasos (cálculo `WorkTimeRecordService`)
  - [x] Fichero único o por empleado (ZIP, `split_by_employee`)
  - [x] Contraseña opcional en Excel (protección de hoja PhpSpreadsheet)
- [x] Informe resumen ausencias: `POST /reports/leave-summary` (Excel) — disponibles, solicitadas, aprobadas, rechazadas, en espera
- [x] Tests: `RegistroHorarioExportTest` — 8 tests verdes (cálculo + descargas Excel/PDF/ZIP + permisos)
- [x] Dependencia añadida: `barryvdh/laravel-dompdf` ^3.1 (faltaba en composer)
- **Nota:** los informes se devuelven como descarga directa (`Content-Disposition: attachment`),
  no como `download_url` firmado del contrato. Envolver con almacenamiento+URL temporal si se requiere.
- [x] **Dashboard admin** (shell): `app/admin/layout.tsx` con guard de rol (admin/super-admin/rrhh-coordinator),
  `Sidebar` generalizado por props, home `/admin`, enrutado por rol desde la home raíz y el login.
- [x] Página `/admin/informes`: filtros (empresa, rango, formato), opciones, descargas Excel/PDF/ZIP + barra visual diaria.
- [x] Página `/admin/empresas`: listado, alta/edición (nombre, CIF, email, teléfono) y **activación de módulos por empresa**
  (RRHH/fichajes, Socios/asociaciones) con toggles. Columnas `module_hr`/`module_associations` en `companies`.
- [x] **Grupos de empresas** (`company_groups` + `companies.company_group_id`): CRUD de grupos, asignación por empresa
  y columna de grupo en el listado (`/admin/empresas`). Borrar grupo desvincula sus empresas (no las borra).
- [x] Página `/admin/entidades`: CRUD completo de entidades (saldo inicial, ejercicio) + gestión de tipos de socio y cuotas.
- [x] Ficha de socio en `/admin/socios`: detalle, edición de datos personales, cambio de estado, historial y registro de pagos.

### Sprint 9-10 — Módulo Socios MVP (semanas 17-20) 🚧
- [x] CRUD Entidades (`entities`): **independientes a nivel de tenant** (no cuelgan de empresa). name, type, cif, address, opening_balance, fiscal_year. Borrado bloqueado si hay socios (`ENTITY_HAS_MEMBERS`).

### Corrección de modelo + Dashboard admin por módulos
- [x] **Modelos por tenant** (`tenant_modules`): RRHH, Socios, Tesorería, Nóminas (futuro), Comunicaciones (futuro), activables individualmente. Endpoints `GET /tenant-modules`, `PATCH /tenant-modules/{key}`. Migrado demo sin pérdida (entidades desacopladas de empresa; flags de módulo movidos de `companies` a `tenant_modules`).
- [x] **Dashboard admin completo**, gating de menú por módulos activos:
  - `/admin/configuracion`: módulos · grupos y empresas · centros · convenios (+ tipos de ausencia) · hitos · festivos · calendarios (plantillas + calendarios)
  - `/admin/empleados`: listado con filtros + ficha con pestañas (datos personales, laboral; formación/documentos/materiales/comportamiento pendientes de backend) + alta + invitación
  - `/admin/fichajes`: vista diaria con barra visual + corrección/borrado con auditoría obligatoria (ET 34.9)
  - `/admin/ausencias`: pendientes (aprobar/rechazar) + listado mensual + resumen de vacaciones
  - `/admin/entidades`, `/admin/socios` (ficha + pagos), `/admin/tesoreria` (KPIs + gastos + pagos): independientes de empresa

### Planes, monetización y operación (SaaS)
- [x] **Planes** (`plans` public): Free/Starter/Professional/Business con `limits` y `modules_allowed` JSON; `plan_overrides` por tenant; `tenants.plan_id` + `trial_ends_at`. `PlanLimitService` (plan + override, caché 5 min) y middleware `plan.limit:{recurso}` → 402 `PLAN_LIMIT_REACHED` en empresas/empleados/entidades/socios.
- [x] **Panel super-admin** (`/superadmin`): dashboard (MRR, tenants por estado, empleados/socios globales), tenants (listado/ficha/alta-provisión/override/cambio de plan/suspensión), CRUD de planes. API `/superadmin/*` (rol super-admin). Usuario demo `superadmin@demo.gestioname.app`.
- [x] **Gestión completa de tenants y usuarios** (super-admin): listado con contadores (empleados/socios) y trial restante + filtros (plan/estado/búsqueda) + acciones rápidas (suspender/activar, cambiar plan, impersonar, borrar). Ficha con usuarios del tenant (roles, último acceso, reset de contraseña vía magic link, cambio de rol, activar/desactivar), toggles de módulos, override e historial. **Impersonar** (`POST .../impersonate` → magic link 5 min al admin). Verificación de magic link en `/auth/magic-link`. Usuarios con `active`/`last_login_at`.
- [x] **Audit log super-admin** (`superadmin_audit_log` public): registra impersonar, cambio de plan/estado, reset password, módulos, override, alta/baja de tenant. Página `/superadmin/auditoria` paginada.
- [x] **Robustez del panel**: timeout de 15 s en el cliente API (`AbortController`) + manejo de error con reintento en todas las pantallas `/superadmin/*` (skeleton loaders, nunca spinner indefinido). Dashboard cacheado 60 s y resiliente a schemas rotos (no tumba el panel global).
- [x] **Registro público** (`/`): landing + onboarding con validación de subdominio en tiempo real; `POST /register` (sin tenant) provisiona tenant Free 30 días trial, módulos según tipo y magic link de bienvenida.
- [x] **Marca blanca**: `tenants.custom_domain` + `tenant_branding`; `TenantMiddleware` resuelve por dominio propio; `GET /branding` público (caché 10 min) aplicado en el frontend (color/logo/nombre).
- [x] **Fichaje**: kiosk `/clock` (PIN, identificación por nombre, hitos, confirmación con hora, auto-reset 5 s, alertas); portal «Mis fichajes» semanal con horas/día e indicador de salida pendiente; admin fichajes con barra visual + alertas (sin fichar / incompletos) + corrección con auditoría.
- [x] **Optimización**: índice `member_payments(member_id, year)` (el resto ya existían); caché Redis de plan limits (5 min) y branding (10 min); `Skeleton` loaders y `useDebounce` (búsquedas). _Pendiente: paginación en cliente y React Query/SWR (los listados ya pagina el backend; el front usa la primera página)._
- [x] **Fichas de empleado** completadas: Formación, Materiales, Comportamiento (CRUD) y Documentos (subida/descarga real a disco). Ficha de socio ya estaba completa.
- [x] Categorías de gasto por defecto al crear entidad (Alquiler, Material, Actos, Seguros)
- [x] CRUD Socios (`members`): ficha completa, `dni` cifrado (LOPD), nº de socio autonumerado por entidad, filtros (estado, tipo, búsqueda)
- [x] CRUD Tipos de socio (`member_types`): name, description, fee_amount, fee_periodicity (anidados bajo entidad)
- [x] CRUD Cuotas y pagos (`member_payments`): importe por defecto = cuota del tipo; estados pagado/parcial/pendiente
- [x] CRUD Gastos (`expenses`): category_id, amount, date, description (filtros categoría/fechas)
- [x] CRUD Categorías de gasto (`expense_categories`): name, color
- [x] Tesorería: `TreasuryService` → saldo_inicial + SUM(ingresos cobrados) - SUM(gastos) = saldo_banco
- [x] Frontend: `/admin/socios` (entidades + socios + altas) y `/admin/tesoreria` (KPIs + gastos)
- [x] Tests: `MemberApiTest` (8), `TreasuryCalculationTest` (2) — verdes
- [ ] **Pendiente:** importación/exportación Excel de socios, backup JSON, PDFs (listado/recibo/ficha),
  dashboard con gráficas, número de plantillas de config por tipo de entidad. (Eventos → Fase 2, Sprint 15-16.)

### Sprint 11-12 — Beta interna + pulido (semanas 21-24)
- [ ] Despliegue en producción con datos reales Datarecover
- [ ] Prueba de uso interno durante 4 semanas
- [ ] Lista de bugs y mejoras recogida del equipo
- [ ] Corrección de bugs críticos y bloqueantes
- [ ] Ajustes UX basados en feedback real
- [ ] Documentación de usuario básica (guía rápida PDF)
- [ ] Onboarding guiado (wizard de configuración inicial para nuevos tenants)

---

## Fase 2 · RRHH completo + Asociaciones (meses 7–9)

### Sprint 13-14 — RRHH avanzado
- [ ] Organigrama visual drag-and-drop en frontend
- [ ] Resumen visual ficha empleado (% datos completados)
- [ ] Exportación masiva fichas empleados a Excel
- [ ] Gestión de contratos: alertas de vencimiento próximo
- [ ] Nóminas: subida de PDF por RRHH, notificación al empleado
- [ ] Módulo de evaluaciones de rendimiento

### Sprint 15-16 — Módulo eventos asociaciones
- [ ] CRUD Eventos (`events`): title, description, date_start, date_end, location, capacity, price
- [ ] Control de asistencia por evento (`event_attendances`): member_id, event_id, status, payment_status
- [ ] Listado público de eventos en portal del socio

### Sprint 17-18 — Comunicaciones y portal del socio
- [ ] Email masivo a socios: plantillas, filtros por tipo/estado, programación
- [ ] Recordatorios automáticos de cuota (job programado)
- [ ] Portal del socio (Next.js PWA):
  - [ ] Login con email/teléfono (magic link)
  - [ ] Ver mi cuota y estado de pago
  - [ ] Histórico de pagos
  - [ ] Próximos eventos + inscripción
  - [ ] Notificaciones push (service worker)
- [ ] Configuración PWA: manifest.json, service worker, iconos

---

## Fase 3 · Contabilidad + Integración a3asesor (meses 10–12)

### Sprint 19-20 — Contabilidad básica
- [ ] Plan de cuentas simplificado PGC (grupos 1-7) por tenant
- [ ] CRUD Asientos contables: fecha, descripción, referencia, líneas (cuenta, debe, haber)
- [ ] Validación cuadre: suma debe = suma haber
- [ ] Registro de facturas emitidas/recibidas con IVA
- [ ] Control de vencimientos: cobros y pagos pendientes
- [ ] Libro diario, balance simplificado, cuenta de resultados

### Sprint 21-22 — Exportación suenlace.dat
- [ ] Servicio `SuenlaceExportService`: genera fichero ASCII 512 bytes/registro
- [ ] Soporte registros: 0, 1, 2, 3, 4, 9, C, N, V
- [ ] Exportación gastos → registro tipo 0
- [ ] Exportación facturas con IVA → registros tipo 1/2 + 9
- [ ] Exportación datos nómina → registro tipo N (Modelo 190)
- [ ] Exportación vencimientos → registro tipo V
- [ ] Exportación cuentas/clientes/proveedores → registro tipo C
- [ ] Tests: `SuenlaceExportTest` con fixtures de a3asesor de referencia
- [ ] Validación: el fichero generado debe pasar la importación en a3asesor Eco demo

### Sprint 23-24 — Lanzamiento comercial
- [ ] Landing page pública en gestioname.es/app
- [ ] Registro de nuevo tenant (onboarding wizard)
- [ ] Integración Stripe: planes Esencial/Profesional/Empresa, facturación mensual/anual
- [ ] Panel de super-admin: listado tenants, métricas, gestión de planes
- [ ] Documentación de usuario completa
- [ ] Política de privacidad y términos de servicio

---

## Backlog — sin fecha asignada

- [ ] App nativa iOS/Android con Capacitor (tras PWA validada)
- [ ] Pagos Bizum vía Redsys (módulo asociaciones)
- [ ] SEPA XML (domiciliaciones cuotas asociaciones)
- [ ] Integración Kit Digital (agente digitalizador)
- [ ] Modelo 182 (declaración donativos asociaciones)
- [ ] Integración AEAT para entidades que declaran
- [ ] Lector biométrico externo vía API (futuro hardware propio)
- [ ] Multiidioma (catalán, euskera, gallego)

---

## Pulido y funcionalidad (post-MVP)

### Bloque A — Calidad UI
- [x] Sistema de **toasts** global (éxito/error/aviso/info, auto-dismiss 4s) + manejo global de 401 (sesión expirada) y 402 (límite de plan) vía eventos de ventana.
- [x] **Confirmación modal** antes de borrar (`useConfirm`), aplicada a borrados.
- [x] Validación inline en formularios clave (email, código de fichaje) + estados de carga en botones + submit deshabilitado con errores.
- [x] **Estados vacíos** con CTA (empleados/socios/empresas) consistentes (`EmptyState`).
- [x] **Responsive móvil**: sidebar colapsable (drawer + hamburger en Header), tablas con scroll horizontal, formularios a una columna; kiosk `/clock` mobile-first verificado.
- [x] **Visual**: avatares con iniciales en listados, badges de estado consistentes, separadores de formulario (`FormSection`), breadcrumb (`Breadcrumb`).

### Bloque B — Funcionalidad
- [x] **PDFs** (DomPDF): recibo de pago, ficha de socio, carnet de socio y listado de socios (`SocioPdfService` + vistas Blade). Botones en Socios.
- [x] **Import/Export Excel**: importar/exportar socios (plantilla + filtros), exportar empleados (import de empleados ya existía). `SocioImportService`, `EmployeeImportService::export`.
- [x] **Backup JSON** de entidad: exportar/importar entidad completa (tipos, socios, pagos, categorías, gastos) — `EntityBackupService`.
- [ ] **Pendiente** (requieren módulos nuevos amplios): portal del empleado ampliado (editar perfil/foto, nóminas, ausencias con calendario, datos laborales), `/admin/comunicaciones` (email masivo socios/empleados, recordatorios automáticos de cuota). El portal básico (fichajes, horario, ausencias, documentos) ya existe.

### Bloque C — Infraestructura (sin pagos reales)
- [x] **Emails transaccionales** con plantilla base HTML branded (`emails/action.blade.php`, colores #0F2756/#68DFB9), aplicada al magic link. _Resto de notificaciones pueden migrar a la misma vista._
- [x] **Stripe placeholder**: `/admin/suscripcion` (plan actual, consumo vs límites con barras de progreso, modal de planes con contacto info@datarecover.es) + endpoint `GET /subscription`. Banners en el dashboard: trial < 7 días y límite ≥ 80% (`PlanBanners`).
- [x] **Deploy**: `docker-compose.prod.yml` (Traefik + Let's Encrypt, enrutado por subdominio/Path), `.env.production.example` documentado, `scripts/deploy.sh` (build → up → migrate + migrate:tenants → health check), y **health endpoint** `GET /health` (BD, Redis, cola).
