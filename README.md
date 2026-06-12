# Gestioname v2

Plataforma SaaS de gestión de RRHH, control de jornada y gestión de asociaciones.
Desarrollada por **Datarecover S.L.** (Majadahonda, Madrid).

## Stack

- **Backend**: Laravel 11 / PHP 8.3
- **Frontend**: React 18 + Next.js 14 (App Router)
- **Base de datos**: PostgreSQL 16 (multi-tenant por schemas)
- **Caché / Colas**: Redis 7
- **Proxy**: Traefik + Let's Encrypt
- **Despliegue**: Docker Compose en deploy.datarecover.cloud

## Setup rápido

```bash
cp .env.example .env
docker compose up -d
docker compose exec backend composer install
docker compose exec backend php artisan key:generate
docker compose exec backend php artisan migrate
docker compose exec backend php artisan tenant:create "Demo" demo
docker compose exec backend php artisan migrate:tenants
docker compose exec backend php artisan db:seed --class=TenantDemoSeeder
cd frontend && npm install && npm run dev
```

Ver `docs/dev-setup.md` para el setup completo.

## Documentación

| Fichero | Contenido |
|---|---|
| `CLAUDE.md` | Contexto para Claude Code |
| `docs/roadmap.md` | Sprints y tareas con checkboxes |
| `docs/db-schema.md` | Esquema SQL completo |
| `docs/api-contracts.md` | Endpoints y payloads |
| `docs/suenlace-dat-spec.md` | Formato a3asesor |
| `docs/architecture-decisions.md` | ADRs |
| `docs/dev-setup.md` | Guía entorno de desarrollo |
| `docs/testing-guide.md` | PHPUnit + Playwright |
| `docs/multi-tenancy.md` | Sistema multi-tenant |
| `docs/security.md` | ET 34.9, GDPR/LOPD |
| `docs/deployment.md` | CI/CD y producción |
