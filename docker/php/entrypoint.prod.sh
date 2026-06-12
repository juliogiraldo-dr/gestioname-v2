#!/bin/sh
set -e

# Cachea configuración y rutas con el entorno de ejecución ya disponible
# (cachear en build congelaría un .env vacío). Idempotente en cada arranque.
php artisan config:cache
php artisan route:cache
php artisan view:cache || true

# Migración y datos base en el arranque (la plataforma no expone consola).
# Idempotente: migrate y los seeders usan firstOrCreate / comprueban existencia.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    # Espera a que la base de datos acepte conexiones.
    for i in $(seq 1 30); do
        if php artisan migrate --force >/dev/null 2>&1; then
            break
        fi
        echo "Esperando a la base de datos... ($i/30)"
        sleep 2
    done

    php artisan migrate --force                 # schema public (tenants, planes, facturación)
    php artisan db:seed --force || true         # planes + tenant demo + superadmin (idempotente)
    php artisan migrate:tenants || true         # pone al día los schemas de todos los tenants
fi

# Ejecuta el comando del contenedor (php-fpm por defecto, o queue:work/schedule/serve).
exec "$@"
