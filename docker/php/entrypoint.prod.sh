#!/bin/sh
set -e

# Cachea configuración y rutas con el entorno de ejecución ya disponible
# (cachear en build congelaría un .env vacío). Idempotente en cada arranque.
php artisan config:cache
php artisan route:cache
php artisan view:cache || true

# Ejecuta el comando del contenedor (php-fpm por defecto, o queue:work/schedule en worker/scheduler).
exec "$@"
