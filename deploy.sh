#!/bin/bash
set -e

echo "=== Starting deployment ==="

# Pull latest changes from main
git pull origin main

# Install/update PHP dependencies (no dev) - Disabled by user request
# composer install --no-dev --optimize-autoloader --no-interaction

# Install/update Node dependencies & build assets - Disabled by user request
# npm ci
# npm run build

# Run database migrations
php artisan migrate --force --no-interaction

# Clear & cache config, routes, views
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Generate Swagger API docs
php artisan l5-swagger:generate

# Restart queue workers (jika pakai queue)
# php artisan queue:restart

echo "=== Deployment complete ==="
