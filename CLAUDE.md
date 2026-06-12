# Gestioname v2 — Contexto del proyecto

Plataforma SaaS de gestión de RRHH, control de jornada y gestión de asociaciones.
Desarrollada por Datarecover S.L. (Majadahonda, Madrid).
Estrategia: uso interno primero → comercialización posterior.

---

## Stack

| Capa | Tecnología |
|---|---|
| Backend | Laravel 11 / PHP 8.3 |
| Base de datos | PostgreSQL 16 (multi-tenant por schemas) |
| Caché / Colas | Redis 7 |
| Frontend | React 18 + Next.js 14 (App Router) |
| Auth | Laravel Sanctum (JWT + magic link) |
| PDF | Laravel DomPDF |
| Excel | PhpSpreadsheet |
| Proxy / SSL | Traefik + Let's Encrypt |
| Despliegue | Docker Compose en deploy.datarecover.cloud |
| CI/CD | GitHub Actions |

Sin dependencias Microsoft. Sin licencias de pago.

---

## Estructura del repositorio

```
/
├── backend/          # Laravel 11 — API, Jobs, Events, Console
├── frontend/         # React + Next.js — dashboard admin + portal empleado
├── docker/           # Dockerfiles PHP-FPM, Nginx, Node
├── docs/             # ADRs, esquemas BD, especificaciones módulos
└── scripts/          # Migraciones batch, seeds, generador suenlace.dat
```

---

## Multi-tenancy

- Cada empresa/entidad tiene su propio **schema PostgreSQL**.
- Schema `public`: solo tablas del sistema (tenants, planes, facturación).
- Identificación de tenant: **subdominio** (`empresa.gestioname.app`) o cabecera `X-Tenant-ID`.
- Migraciones: comando Artisan custom que corre todas las migraciones en cada schema.
- **NUNCA** usar el schema `public` para datos de negocio de un tenant.

---

## Comandos frecuentes

```bash
# Backend
composer install
php artisan migrate              # migra schema del tenant activo
php artisan migrate:tenants      # migra TODOS los schemas
php artisan db:seed --class=TenantDemoSeeder
php artisan test                 # PHPUnit
php artisan test --filter=NombreTest

# Frontend
npm install
npm run dev
npm run build
npm run typecheck

# Docker
docker compose up -d
docker compose exec backend bash
docker compose exec backend php artisan tinker
docker compose logs -f backend

# Suenlace.dat
php artisan suenlace:export {tenant_id} {ejercicio}
```

---

## Convenciones de código

**PHP / Laravel**
- PSR-12 estricto.
- Tipado estricto en todos los archivos: `declare(strict_types=1)`.
- Un controller por recurso, lógica de negocio en Services.
- Request classes para validación (no validar en controllers).
- Eventos + Listeners para efectos secundarios (email, auditoría).
- Tests PHPUnit para todos los Services y Jobs críticos.

**TypeScript / React**
- TypeScript estricto (`strict: true` en tsconfig).
- Componentes funcionales + hooks. Sin class components.
- Tailwind CSS para estilos. Sin CSS modules ni styled-components.
- Zod para validación de formularios en cliente.

**Migraciones**
- Siempre incluir `foreign keys` con `onDelete('cascade')` donde corresponda.
- Índices en columnas usadas en WHERE frecuentes (tenant_id, employee_id, date).
- Nunca modificar una migración ya ejecutada en producción — crear nueva.

**Git**
- Ramas: `feature/`, `fix/`, `chore/`
- Commits en español, imperativo: "Añade módulo de convenios"
- PR obligatorio para main. No push directo.

---

## Roles del sistema

| Rol | Acceso |
|---|---|
| `super-admin` | Todo (uso interno Datarecover) |
| `admin` | Toda la empresa/entidad |
| `rrhh-coordinator` | Gestión de empleados a su cargo |
| `operator` | Fichar + portal propio |
| `employee` | Fichar + portal propio |
| `member` | Portal del socio (asociaciones) |

Gestión de roles con **Spatie Laravel Permission**.

---

## Módulos — estado actual

Consultar el estado detallado en `@docs/roadmap.md`.

Resumen de fases:
- **Fase 1 (meses 1-6):** MVP RRHH + socios básico
- **Fase 2 (meses 7-9):** RRHH completo + asociaciones + portal socio
- **Fase 3 (meses 10-12):** Contabilidad + suenlace.dat

---

## Normativa crítica

- **ET art. 34.9**: el registro de jornada es inmutable. Las correcciones manuales
  requieren entrada en tabla `attendance_corrections` con usuario, timestamp,
  valor anterior y posterior. Nunca modificar el registro original.
- **LOPD / GDPR**: campos sensibles (IBAN, DNI) cifrados en BD con `encrypt()`/`decrypt()` de Laravel.
- **Conservación**: registros de jornada y fichajes deben conservarse mínimo 4 años.

---

## suenlace.dat — referencia rápida

Fichero ASCII secuencial, 512 bytes por registro, para a3asesor Eco/Con.
Documentación completa: `@docs/suenlace-dat-spec.md`

Tipos de registro usados en este proyecto:
- `0` — Apuntes sin IVA (gastos, nóminas)
- `1/2` — Cabecera facturas con IVA
- `9` — Detalle líneas IVA
- `N` — Registro Modelo 190 (datos nómina para IRPF)
- `V` — Vencimientos de cobro/pago
- `C` — Alta/modificación cuentas

---

## Documentación de referencia

- `@docs/memoria-tecnica.md` — Especificación completa del proyecto
- `@docs/roadmap.md` — Estado de sprints y tareas pendientes
- `@docs/db-schema.md` — Esquema de base de datos por módulo
- `@docs/suenlace-dat-spec.md` — Especificación técnica formato a3asesor
- `@docs/api-contracts.md` — Contratos de la API REST

---

## Lo que NO hacer

- No usar `WidthType.PERCENTAGE` en tablas docx (rompe en Google Docs).
- No push directo a `main`.
- No lógica de negocio en controllers — siempre en Services.
- No consultas SQL crudas — usar Eloquent o Query Builder.
- No almacenar datos de un tenant en el schema `public`.
- No ignorar errores de validación — siempre lanzar `ValidationException`.
