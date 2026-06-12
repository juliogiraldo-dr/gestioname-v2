# Despliegue — Gestioname v2

> Servidor: deploy.datarecover.cloud
> Proxy: Traefik v3 + Let's Encrypt automático
> Contenedores: Docker Compose

---

## Arquitectura de producción

```
Internet
    │
    ▼
Traefik (443/80)
  ├── *.gestioname.app → backend (Laravel API) :8000
  ├── *.gestioname.app → frontend (Next.js) :3000
  └── deploy.datarecover.cloud → panel Traefik
    │
    ├── PostgreSQL :5432 (interno, no expuesto)
    ├── Redis :6379 (interno, no expuesto)
    └── MinIO :9000 (interno) — storage de ficheros
```

---

## Variables de entorno en producción

```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.gestioname.app

DB_CONNECTION=pgsql
DB_HOST=postgres         # nombre del servicio Docker
DB_PORT=5432
DB_DATABASE=gestioname
DB_USERNAME=gestioname
DB_PASSWORD=***          # secreto en GitHub Actions

REDIS_HOST=redis
REDIS_PASSWORD=***

MAIL_MAILER=postmark
POSTMARK_TOKEN=***

AWS_ACCESS_KEY_ID=***    # MinIO propio
AWS_SECRET_ACCESS_KEY=***
AWS_DEFAULT_REGION=eu-west-1
AWS_BUCKET=gestioname
AWS_ENDPOINT=https://minio.datarecover.cloud
AWS_USE_PATH_STYLE_ENDPOINT=true

FILESYSTEM_DISK=s3

# Stripe
STRIPE_KEY=pk_live_***
STRIPE_SECRET=sk_live_***
STRIPE_WEBHOOK_SECRET=whsec_***

# Sanctum
SANCTUM_STATEFUL_DOMAINS=*.gestioname.app
SESSION_DOMAIN=.gestioname.app
```

---

## Pipeline de despliegue (GitHub Actions)

```yaml
# .github/workflows/deploy.yml
name: Deploy to production

on:
  push:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Run tests
        run: |
          composer install
          php artisan test

  deploy:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Build and push Docker images
        run: |
          docker build -t ghcr.io/datarecover/gestioname-backend:${{ github.sha }} ./backend
          docker build -t ghcr.io/datarecover/gestioname-frontend:${{ github.sha }} ./frontend
          docker push ghcr.io/datarecover/gestioname-backend:${{ github.sha }}
          docker push ghcr.io/datarecover/gestioname-frontend:${{ github.sha }}

      - name: Deploy to server
        uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.DEPLOY_HOST }}
          username: ${{ secrets.DEPLOY_USER }}
          key: ${{ secrets.DEPLOY_SSH_KEY }}
          script: |
            cd /opt/gestioname
            export IMAGE_TAG=${{ github.sha }}
            docker compose pull
            docker compose up -d --no-deps backend frontend worker
            docker compose exec -T backend php artisan migrate:tenants --force
            docker compose exec -T backend php artisan config:cache
            docker compose exec -T backend php artisan route:cache
            docker compose exec -T backend php artisan view:cache
            docker system prune -f

      - name: Health check
        run: |
          sleep 15
          curl -f https://api.gestioname.app/health || exit 1
```

---

## docker-compose.yml producción

```yaml
version: "3.9"

services:
  backend:
    image: ghcr.io/datarecover/gestioname-backend:${IMAGE_TAG}
    restart: unless-stopped
    environment:
      - APP_ENV=production
    env_file: .env.production
    volumes:
      - storage:/var/www/storage
    depends_on:
      - postgres
      - redis
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.backend.rule=Host(`{subdomain}.gestioname.app`) && PathPrefix(`/api`)"
      - "traefik.http.routers.backend.tls.certresolver=letsencrypt"

  worker:
    image: ghcr.io/datarecover/gestioname-backend:${IMAGE_TAG}
    restart: unless-stopped
    command: php artisan queue:work --sleep=3 --tries=3 --timeout=90
    env_file: .env.production
    depends_on:
      - postgres
      - redis

  frontend:
    image: ghcr.io/datarecover/gestioname-frontend:${IMAGE_TAG}
    restart: unless-stopped
    env_file: .env.frontend.production
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.frontend.rule=Host(`{subdomain}.gestioname.app`)"
      - "traefik.http.routers.frontend.tls.certresolver=letsencrypt"

  postgres:
    image: postgres:16-alpine
    restart: unless-stopped
    volumes:
      - pgdata:/var/lib/postgresql/data
    environment:
      POSTGRES_DB: gestioname
      POSTGRES_USER: gestioname
      POSTGRES_PASSWORD: ${DB_PASSWORD}

  redis:
    image: redis:7-alpine
    restart: unless-stopped
    command: redis-server --requirepass ${REDIS_PASSWORD}
    volumes:
      - redisdata:/data

  minio:
    image: minio/minio
    restart: unless-stopped
    command: server /data --console-address ":9001"
    volumes:
      - minio:/data
    environment:
      MINIO_ROOT_USER: ${MINIO_USER}
      MINIO_ROOT_PASSWORD: ${MINIO_PASSWORD}

volumes:
  pgdata:
  redisdata:
  minio:
  storage:
```

---

## Comandos de mantenimiento en producción

```bash
# SSH al servidor
ssh deploy@deploy.datarecover.cloud
cd /opt/gestioname

# Ver estado de contenedores
docker compose ps

# Ver logs en tiempo real
docker compose logs -f backend
docker compose logs -f worker

# Ejecutar comandos Artisan
docker compose exec backend php artisan migrate:tenants --force
docker compose exec backend php artisan queue:restart
docker compose exec backend php artisan cache:clear

# Backup manual de un tenant
docker compose exec postgres pg_dump -U gestioname -n empresa1 gestioname \
  > /backups/empresa1_manual_$(date +%Y%m%d_%H%M).sql

# Restaurar tenant desde backup
docker compose exec -T postgres psql -U gestioname gestioname \
  < /backups/empresa1_manual_20260611.sql

# Ver colas Redis
docker compose exec redis redis-cli -a $REDIS_PASSWORD INFO keyspace
docker compose exec redis redis-cli -a $REDIS_PASSWORD LLEN queues:default
```

---

## Staging

URL: `https://staging.gestioname.app`
Deploy: automático en cada merge a `main`.
Datos: base de datos separada, datos de demo.
Credenciales: mismas que dev (ver `docs/dev-setup.md`).

```bash
# Deploy manual a staging (sin esperar CI)
make deploy-staging
# Equivale a: ssh staging "cd /opt/gestioname-staging && docker compose pull && docker compose up -d"
```

---

## Rollback

```bash
# Ver últimas imágenes disponibles
docker images ghcr.io/datarecover/gestioname-backend --format "{{.Tag}}: {{.CreatedAt}}"

# Rollback a una versión anterior
IMAGE_TAG=abc123def456 docker compose up -d backend frontend worker

# Si hay migraciones problemáticas:
docker compose exec backend php artisan migrate:rollback --step=1
# (solo schema public — para tenants hay que hacerlo manualmente)
```

---

## Health checks

```bash
# Endpoint de salud (no requiere auth)
GET https://api.gestioname.app/health
# Response: { "status": "ok", "db": "ok", "redis": "ok", "queue": "ok" }

# Monitorización con Grafana
# Dashboard: https://grafana.datarecover.cloud/d/gestioname
# Alertas: Slack #alertas-gestioname
```
