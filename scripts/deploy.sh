#!/usr/bin/env bash
# Despliegue de Gestioname v2 en deploy.datarecover.cloud.
# Uso (en el servidor):  ./scripts/deploy.sh
set -euo pipefail

COMPOSE="docker compose -f docker-compose.prod.yml"

echo "▶ 1/6 · Actualizando código (git pull)…"
git pull --ff-only

echo "▶ 2/6 · Construyendo imágenes…"
$COMPOSE build

echo "▶ 3/6 · Levantando servicios…"
$COMPOSE up -d

echo "▶ 4/6 · Dependencias y caché de configuración…"
$COMPOSE exec -T backend composer install --no-dev --optimize-autoloader
$COMPOSE exec -T backend php artisan config:cache
$COMPOSE exec -T backend php artisan route:cache

echo "▶ 5/6 · Migraciones (sistema + todos los tenants)…"
$COMPOSE exec -T backend php artisan migrate --force
$COMPOSE exec -T backend php artisan migrate:tenants

echo "▶ 6/6 · Reiniciando workers y comprobando salud…"
$COMPOSE restart worker scheduler
sleep 3
curl -fsS https://api.gestioname.app/health >/dev/null && echo "✓ Health OK" || echo "⚠ Health check no respondió 200 — revisar logs"

echo "✅ Despliegue completado."
