#!/bin/bash
# Backup diario de todos los schemas de tenants
# Ejecutar con cron: 0 2 * * * /opt/gestioname/scripts/backup-tenants.sh

set -e

BACKUP_DIR="/backups/gestioname"
DATE=$(date +%Y%m%d_%H%M)
DB_HOST="${DB_HOST:-postgres}"
DB_USER="${DB_USER:-gestioname}"
DB_NAME="${DB_NAME:-gestioname}"
RETENTION_DAYS=30

mkdir -p "$BACKUP_DIR"

# Obtener lista de schemas (excluyendo public, pg_catalog, information_schema)
SCHEMAS=$(psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -t -c \
  "SELECT schema_name FROM information_schema.schemata
   WHERE schema_name NOT IN ('public','pg_catalog','information_schema','pg_toast')
   AND schema_name NOT LIKE 'pg_%'")

for SCHEMA in $SCHEMAS; do
  SCHEMA=$(echo "$SCHEMA" | xargs)  # trim whitespace
  if [ -n "$SCHEMA" ]; then
    OUTFILE="$BACKUP_DIR/${SCHEMA}_${DATE}.sql.gz"
    echo "Backing up schema: $SCHEMA → $OUTFILE"
    pg_dump -h "$DB_HOST" -U "$DB_USER" -n "$SCHEMA" "$DB_NAME" | gzip > "$OUTFILE"
  fi
done

# Limpiar backups antiguos
find "$BACKUP_DIR" -name "*.sql.gz" -mtime +$RETENTION_DAYS -delete
echo "Backup completado: $(date)"
