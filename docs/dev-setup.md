# Configuración del entorno de desarrollo

---

## Prerequisitos

| Herramienta | Versión mínima | Verificar |
|---|---|---|
| PHP | 8.3 | `php -v` |
| Composer | 2.x | `composer --version` |
| Node.js | 20 LTS | `node -v` |
| npm | 10.x | `npm -v` |
| Docker Engine | 24+ | `docker --version` |
| Docker Compose | v2 | `docker compose version` |
| Git | 2.40+ | `git --version` |

---

## Primer setup (desde cero)

```bash
# 1. Clonar el repositorio
git clone git@github.com:datarecover/gestioname-v2.git
cd gestioname-v2

# 2. Variables de entorno
cp .env.example .env
# Editar .env con los valores de desarrollo local (ver sección Variables de entorno)

# 3. Levantar servicios Docker
docker compose up -d

# 4. Instalar dependencias PHP
docker compose exec backend composer install

# 5. Generar clave de aplicación
docker compose exec backend php artisan key:generate

# 6. Ejecutar migraciones del sistema (schema public)
docker compose exec backend php artisan migrate

# 7. Crear tenant de demo
docker compose exec backend php artisan tenant:create "Datarecover Demo" demo

# 8. Ejecutar migraciones del tenant demo
docker compose exec backend php artisan migrate:tenants

# 9. Seed de datos demo
docker compose exec backend php artisan db:seed --class=TenantDemoSeeder

# 10. Instalar dependencias Node (frontend)
cd frontend && npm install

# 11. Arrancar frontend en modo dev
npm run dev
```

Tras estos pasos:
- Backend API: `http://localhost:8000/api/v1`
- Frontend: `http://localhost:3000`
- Portal demo: `http://demo.localhost:3000`
- Mailpit (emails): `http://localhost:8025`
- PostgreSQL: `localhost:5432` (user: gestioname, pass: secret)
- Redis: `localhost:6379`

---

## Servicios Docker

```yaml
# docker-compose.yml — servicios incluidos
services:
  backend:    # Laravel — php-fpm + nginx
  postgres:   # PostgreSQL 16
  redis:      # Redis 7
  mailpit:    # Captura de emails en desarrollo (http://localhost:8025)
  frontend:   # Next.js en modo dev (hot reload)
```

```bash
# Comandos útiles
docker compose up -d          # Arrancar todos
docker compose down           # Parar todos
docker compose logs -f backend  # Ver logs del backend
docker compose exec backend bash  # Shell en el contenedor PHP
docker compose exec postgres psql -U gestioname  # PostgreSQL CLI
```

---

## Variables de entorno (.env)

Variables críticas que hay que configurar en desarrollo:

```bash
# App
APP_NAME="Gestioname v2"
APP_ENV=local
APP_KEY=          # generada con artisan key:generate
APP_URL=http://localhost:8000

# Multi-tenancy
TENANT_DOMAIN=localhost  # dominio base para subdominios

# Base de datos
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=gestioname
DB_USERNAME=gestioname
DB_PASSWORD=secret

# Redis
REDIS_HOST=redis
REDIS_PORT=6379

# Email
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_FROM_ADDRESS=noreply@gestioname.app
MAIL_FROM_NAME="Gestioname"

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:3000,demo.localhost:3000

# Storage
FILESYSTEM_DISK=local

# Frontend URL
FRONTEND_URL=http://localhost:3000
```

---

## Comandos Artisan frecuentes

```bash
# Tenants
php artisan tenant:create {name} {subdomain}  # Crear nuevo tenant
php artisan tenant:list                        # Listar tenants
php artisan migrate:tenants                    # Migrar todos los schemas
php artisan migrate:tenants --tenant={id}      # Migrar solo un tenant

# Base de datos
php artisan migrate                  # Migrar schema public
php artisan migrate:fresh            # Reset + migrate schema public
php artisan db:seed                  # Seed schema public
php artisan db:seed --class=TenantDemoSeeder  # Seed tenant demo

# Testing
php artisan test                     # Todos los tests
php artisan test --filter=Auth       # Tests que contengan "Auth" en el nombre
php artisan test --coverage          # Con cobertura (requiere Xdebug)

# Cola de trabajos
php artisan queue:work               # Procesar jobs (emails, exportaciones)
php artisan queue:work --queue=emails  # Solo cola de emails

# Exportaciones
php artisan suenlace:export {tenant_id} {year}  # Generar suenlace.dat

# Caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

## Makefile — atajos

```bash
make up           # docker compose up -d
make down         # docker compose down
make bash         # docker compose exec backend bash
make test         # docker compose exec backend php artisan test
make migrate      # docker compose exec backend php artisan migrate
make migrate-all  # docker compose exec backend php artisan migrate:tenants
make logs         # docker compose logs -f backend
make lint         # pint (PHP) + eslint (TS)
```

---

## Estructura de directorios backend

```
backend/
├── app/
│   ├── Console/Commands/        # Comandos Artisan (TenantCreate, MigrateTenants...)
│   ├── Http/
│   │   ├── Controllers/Api/     # Un controller por recurso
│   │   ├── Middleware/          # TenantMiddleware, etc.
│   │   └── Requests/            # Form Request classes (validación)
│   ├── Models/                  # Eloquent models
│   ├── Services/                # Lógica de negocio
│   │   ├── Attendance/          # AttendanceService, RegistroHorarioService
│   │   ├── Suenlace/            # SuenlaceExportService
│   │   └── ...
│   ├── Jobs/                    # Jobs asíncronos (exportaciones, emails masivos)
│   └── Events/ + Listeners/     # Eventos del dominio
├── database/
│   ├── migrations/              # Migraciones (schema public y tenant)
│   ├── seeders/
│   └── factories/
├── resources/
│   └── views/pdf/               # Plantillas Blade para PDFs
├── routes/
│   ├── api.php                  # Rutas API (prefijo /api/v1)
│   └── channels.php             # Broadcasting
└── tests/
    ├── Feature/                 # Tests de integración (API)
    ├── Unit/                    # Tests unitarios (Services)
    └── fixtures/suenlace/       # Fixtures a3asesor para tests
```

---

## Estructura de directorios frontend

```
frontend/
├── app/
│   ├── (admin)/                 # Dashboard administrador (layout admin)
│   │   ├── employees/
│   │   ├── attendance/
│   │   ├── members/
│   │   └── ...
│   ├── (portal)/                # Portal del empleado (layout portal)
│   │   ├── me/
│   │   └── clock/               # Reloj de fichar kiosk
│   └── (member-portal)/         # Portal del socio (layout socio)
├── components/
│   ├── ui/                      # Componentes base (Button, Input, Table...)
│   ├── admin/                   # Componentes específicos del admin
│   └── portal/                  # Componentes del portal empleado
├── lib/
│   ├── api.ts                   # Cliente API (fetch + auth headers)
│   ├── types.ts                 # Tipos TypeScript compartidos
│   └── utils.ts                 # Helpers
└── public/
    └── icons/                   # Iconos PWA
```

---

## Convenciones Git

```bash
# Ramas
feature/nombre-descriptivo    # Nueva funcionalidad
fix/descripcion-del-bug       # Corrección de bug
chore/tarea-sin-codigo        # Cambios de infra, deps, docs

# Commits (imperativo, en español)
git commit -m "Añade módulo de convenios con tipos de ausencia"
git commit -m "Corrige cálculo de sobretiempo en días festivos"
git commit -m "Actualiza dependencias PHP a versiones parcheadas"

# Workflow
git checkout -b feature/mi-feature
# ... trabajar ...
git push origin feature/mi-feature
# Abrir PR a main
# CI pasa → merge
```

---

## Cuentas de acceso en desarrollo

Tras ejecutar el seeder demo:

| Email | Contraseña | Rol |
|---|---|---|
| admin@demo.gestioname.app | password | admin |
| coordinator@demo.gestioname.app | password | rrhh-coordinator |
| employee@demo.gestioname.app | password | employee |
| member@demo.gestioname.app | password | member |

Portal demo: `http://demo.localhost:3000`
Código de fichaje demo empleado: `12345678`
