#!/bin/bash
set -e

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Linking storage..."
php artisan storage:link --force 2>/dev/null || true

echo "==> Caching config, routes, views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Starting server on port ${PORT:-8000}..."
php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
