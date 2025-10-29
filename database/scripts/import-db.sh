#!/usr/bin/env bash

set -euo pipefail

# Import database/schema.sql and database/seed.sql into the local MAMP MySQL.

# Resolve paths
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
ENV_FILE="$PROJECT_ROOT/.env"
SCHEMA_FILE="$PROJECT_ROOT/database/schema.sql"
SEED_FILE="$PROJECT_ROOT/database/seed.sql"

# Load environment variables
if [[ -f "$ENV_FILE" ]]; then
	set -a
	source "$ENV_FILE"
	set +a
else
	echo "[import-db] Missing .env at $ENV_FILE" >&2
	exit 1
fi

# Defaults
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-8889}
DB_USER=${DB_USER:-root}
DB_PASS=${DB_PASS:-root}
DB_NAME=${DB_NAME:-waste_not_kitchen}

# Prefer MAMP's bundled mysql client if available (mysql80 > mysql57)
if [[ -x "/Applications/MAMP/Library/bin/mysql80/bin/mysql" ]]; then
	MYSQL_BIN="/Applications/MAMP/Library/bin/mysql80/bin/mysql"
elif [[ -x "/Applications/MAMP/Library/bin/mysql57/bin/mysql" ]]; then
	MYSQL_BIN="/Applications/MAMP/Library/bin/mysql57/bin/mysql"
else
	MYSQL_BIN="mysql"
fi

# Prefer the MAMP socket if it exists; otherwise, use host/port
MAMP_SOCKET="/Applications/MAMP/tmp/mysql/mysql.sock"
MYSQL_ARGS=("-u" "$DB_USER" "-p$DB_PASS")
if [[ -S "$MAMP_SOCKET" ]]; then
	MYSQL_ARGS=("--socket=$MAMP_SOCKET" "-u" "$DB_USER" "-p$DB_PASS")
else
	MYSQL_ARGS=("-h" "$DB_HOST" "-P" "$DB_PORT" "-u" "$DB_USER" "-p$DB_PASS")
fi

echo "[import-db] Ensuring database exists: $DB_NAME"
"$MYSQL_BIN" "${MYSQL_ARGS[@]}" -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`;"

if [[ -f "$SCHEMA_FILE" ]]; then
	echo "[import-db] Importing schema from $SCHEMA_FILE"
	"$MYSQL_BIN" "${MYSQL_ARGS[@]}" "$DB_NAME" < "$SCHEMA_FILE"
else
	echo "[import-db] Warning: schema file not found at $SCHEMA_FILE" >&2
fi

if [[ -f "$SEED_FILE" ]]; then
	echo "[import-db] Importing seed data from $SEED_FILE"
	"$MYSQL_BIN" "${MYSQL_ARGS[@]}" "$DB_NAME" < "$SEED_FILE"
else
	echo "[import-db] Note: seed file not found at $SEED_FILE (skipping)"
fi

echo "[import-db] Done."

