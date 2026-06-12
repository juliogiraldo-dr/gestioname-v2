# Arquitectura (resumen)

## Stack

| Capa | Tecnología |
|---|---|
| Backend | Laravel 11 / PHP 8.4 |
| Base de datos | PostgreSQL 16 (multi-tenant por **schemas**) |
| Caché / Colas | Redis 7 |
| Frontend | React 19 + Next.js 16 (App Router, salida `standalone`) |
| Auth | Laravel Sanctum (token) + magic link |
| Estado cliente | React Query (`@tanstack/react-query`) |
| Despliegue | Docker + plataforma Datarecover (deploy.datarecover.cloud) |
| CI/CD | GitHub Actions (CI: lint+tests; Deploy: imágenes a GHCR) |

## Multi-tenancy

Cada cliente (tenant) tiene su **propio schema PostgreSQL**. El schema `public` solo
guarda tablas del sistema (tenants, planes, branding, auditoría). El tenant se resuelve
por subdominio (`empresa.gestioname.app`), por dominio propio o por cabecera `X-Tenant-ID`.

## Comandos útiles

```bash
docker compose up -d
docker compose exec backend php artisan migrate            # schema public
docker compose exec backend php artisan migrate:tenants    # todos los schemas de tenant
docker compose exec backend php artisan db:seed            # planes + tenant demo + superadmin
docker compose exec backend php artisan test
docker compose exec backend ./vendor/bin/pint app          # lint PHP (PSR-12)
docker compose exec frontend npm run typecheck
docker compose exec frontend npm run lint
docker compose exec frontend npm run build
```

- Variables de entorno: ver `.env.example` (backend) y `docker-compose.yml`.
- Comandos programados (scheduler): `reminders:quota` (recordatorio de cuota) y
  `trials:notify` (avisos de trial), una pasada diaria.

## Repositorio

- **Repo**: github.com/juliogiraldo-dr/gestioname-v2
- **Ramas**: `main` (producción), `develop` (desarrollo)
- Imágenes de producción: `ghcr.io/juliogiraldo-dr/gestioname-backend` y `…-frontend`
  (construidas por GitHub Actions al hacer merge a `main`).
