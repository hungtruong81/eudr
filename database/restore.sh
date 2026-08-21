#!/usr/bin/env bash
set -euo pipefail

DB_NAME="${DB_NAME:-eudr}"
DB_USER="${DB_USER:-eudr}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"

: "${DB_PASSWORD:?Set DB_PASSWORD before running restore}"
export MYSQL_PWD="$DB_PASSWORD"

mysql --protocol=tcp -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -e "CREATE DATABASE IF NOT EXISTS \\"$DB_NAME\\" CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;"
mysql --protocol=tcp -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" < "$(dirname "$0")/schema.sql"
mysql --protocol=tcp -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" < "$(dirname "$0")/seed.sql"
printf 'Database restored: %s\n' "$DB_NAME"
