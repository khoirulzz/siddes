#!/usr/bin/env bash
# =============================================================
# Render.com Start Script — Laravel 12 + Aiven MySQL + SSL
# =============================================================
set -eu

PORT="${PORT:-10000}"
RUN_MIGRATIONS="${RUN_MIGRATIONS:-true}"
MIGRATION_RETRIES="${MIGRATION_RETRIES:-10}"
MIGRATION_RETRY_SLEEP="${MIGRATION_RETRY_SLEEP:-5}"

echo "==> [render-start] Starting app on port ${PORT}"

# Create storage symlink (idempotent)
php artisan storage:link > /dev/null 2>&1 || true

# Clear and re-cache in case env vars changed after build
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations with retry
if [ "$RUN_MIGRATIONS" = "true" ]; then
  echo "==> [render-start] Running migrations..."
  attempt=1
  while [ "$attempt" -le "$MIGRATION_RETRIES" ]; do
    if php artisan migrate --force --no-interaction; then
      echo "==> [render-start] Migration successful!"
      break
    fi

    if [ "$attempt" -eq "$MIGRATION_RETRIES" ]; then
      echo "==> [render-start] Migration failed after ${MIGRATION_RETRIES} attempts. Exiting."
      exit 1
    fi

    echo "==> [render-start] Migration attempt ${attempt}/${MIGRATION_RETRIES} failed, retrying in ${MIGRATION_RETRY_SLEEP}s..."
    attempt=$((attempt + 1))
    sleep "$MIGRATION_RETRY_SLEEP"
  done
fi

echo "==> [render-start] Launching PHP server..."
exec php artisan serve --host=0.0.0.0 --port="${PORT}"
