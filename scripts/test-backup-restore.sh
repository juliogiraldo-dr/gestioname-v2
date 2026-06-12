#!/bin/bash
# Verifica que el backup de un tenant es restaurable
# Uso: ./scripts/test-backup-restore.sh empresa1

set -e
TENANT=$1
BACKUP_FILE=$(ls -t /backups/gestioname/${TENANT}_*.sql.gz | head -1)
TEST_SCHEMA="${TENANT}_restore_test"

echo "Probando restauración de: $BACKUP_FILE"

psql -U gestioname gestioname -c "DROP SCHEMA IF EXISTS $TEST_SCHEMA CASCADE"
psql -U gestioname gestioname -c "CREATE SCHEMA $TEST_SCHEMA"

gunzip -c "$BACKUP_FILE" | \
  sed "s/SET search_path = $TENANT/SET search_path = $TEST_SCHEMA/" | \
  psql -U gestioname gestioname

TABLES=$(psql -U gestioname gestioname -t -c \
  "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$TEST_SCHEMA'")

echo "Tablas restauradas: $TABLES"
psql -U gestioname gestioname -c "DROP SCHEMA $TEST_SCHEMA CASCADE"
echo "Test de restauración OK"
