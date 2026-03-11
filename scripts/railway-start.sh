#!/usr/bin/env sh
set -eu

PORT="${PORT:-8080}"
RUN_MIGRATIONS="${RUN_MIGRATIONS:-false}"
MIGRATION_RETRIES="${MIGRATION_RETRIES:-20}"
MIGRATION_RETRY_SLEEP="${MIGRATION_RETRY_SLEEP:-3}"

echo "[railway] starting app on port ${PORT}"

# Idempotent in production; ignore if symlink already exists.
php artisan storage:link >/dev/null 2>&1 || true

if [ "$RUN_MIGRATIONS" = "true" ]; then
  echo "[railway] RUN_MIGRATIONS=true, running migrate with retry..."
  attempt=1
  while [ "$attempt" -le "$MIGRATION_RETRIES" ]; do
    if php artisan migrate --force --no-interaction; then
      echo "[railway] migrate success"
      break
    fi

    if [ "$attempt" -eq "$MIGRATION_RETRIES" ]; then
      echo "[railway] migrate failed after ${MIGRATION_RETRIES} attempts"
      exit 1
    fi

    echo "[railway] migrate failed, retry ${attempt}/${MIGRATION_RETRIES} in ${MIGRATION_RETRY_SLEEP}s..."
    attempt=$((attempt + 1))
    sleep "$MIGRATION_RETRY_SLEEP"
  done
fi

exec php artisan serve --host=0.0.0.0 --port="$PORT"
