#!/usr/bin/env bash

set -euo pipefail

# Export the current database schema (DDL) and data (DML) from MAMP MySQL
# into database/schema.sql and database/seed.sql so they can be committed to Git.

# Resolve paths
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
ENV_FILE="$PROJECT_ROOT/.env"
SCHEMA_FILE="$PROJECT_ROOT/database/schema.sql"
SEED_FILE="$PROJECT_ROOT/database/seed.sql"

# Load environment variables from .env if present
if [[ -f "$ENV_FILE" ]]; then
	# shellcheck disable=SC2046
	set -a
	source "$ENV_FILE"
	set +a
else
	echo "[export-db] Missing .env at $ENV_FILE" >&2
	exit 1
fi

# Defaults if not set
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-8889}
DB_USER=${DB_USER:-root}
DB_PASS=${DB_PASS:-root}
DB_NAME=${DB_NAME:-waste_not_kitchen}

# Prefer MAMP's bundled mysqldump if available (mysql80 > mysql57)
if [[ -x "/Applications/MAMP/Library/bin/mysql80/bin/mysqldump" ]]; then
	MYSQLDUMP_BIN="/Applications/MAMP/Library/bin/mysql80/bin/mysqldump"
elif [[ -x "/Applications/MAMP/Library/bin/mysql57/bin/mysqldump" ]]; then
	MYSQLDUMP_BIN="/Applications/MAMP/Library/bin/mysql57/bin/mysqldump"
else
	MYSQLDUMP_BIN="mysqldump"
fi

# Prefer the MAMP socket if it exists; otherwise, use host/port
MAMP_SOCKET="/Applications/MAMP/tmp/mysql/mysql.sock"
MYSQL_ARGS=("-u" "$DB_USER" "-p$DB_PASS")
if [[ -S "$MAMP_SOCKET" ]]; then
	MYSQL_ARGS=("--socket=$MAMP_SOCKET" "-u" "$DB_USER" "-p$DB_PASS")
else
	MYSQL_ARGS=("-h" "$DB_HOST" "-P" "$DB_PORT" "-u" "$DB_USER" "-p$DB_PASS")
fi

echo "[export-db] Dumping schema to $SCHEMA_FILE"
"$MYSQLDUMP_BIN" "${MYSQL_ARGS[@]}" \
	--no-data \
	--routines \
	--events \
	--triggers \
	"$DB_NAME" > "$SCHEMA_FILE"

echo "[export-db] Dumping data to $SEED_FILE"
"$MYSQLDUMP_BIN" "${MYSQL_ARGS[@]}" \
	--no-create-info \
	--skip-triggers \
	"$DB_NAME" > "$SEED_FILE"

echo "[export-db] Done. Updated:\n  - $SCHEMA_FILE\n  - $SEED_FILE"

