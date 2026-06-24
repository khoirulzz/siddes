#!/usr/bin/env bash
# =============================================================
# Render.com Build Script — Laravel 12 + Aiven MySQL
# =============================================================
set -eu

echo "==> [render-build] PHP version"
php --version

echo "==> [render-build] Installing Composer dependencies (no dev)"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

echo "==> [render-build] Installing Node dependencies"
npm ci

echo "==> [render-build] Building frontend assets (Vite)"
npm run build

echo "==> [render-build] Caching Laravel config/routes/views"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> [render-build] Build complete!"
