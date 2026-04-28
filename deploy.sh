#!/bin/bash
set -e

echo "=== Starting deployment ==="

# FIX: allow git repo ownership
git config --global --add safe.directory '*'

# Pull latest code
git pull origin main

# Clear old caches dulu agar tidak load provider lama
docker exec layanan_pajak_app rm -f bootstrap/cache/config.php
docker exec layanan_pajak_app rm -f bootstrap/cache/packages.php
docker exec layanan_pajak_app rm -f bootstrap/cache/services.php

# Fix storage permissions
docker exec layanan_pajak_app chmod -R 775 storage bootstrap/cache
docker exec layanan_pajak_app chown -R www-data:www-data storage bootstrap/cache

# Install dependencies tanpa dev packages
docker exec layanan_pajak_app composer install --no-dev --optimize-autoloader --no-interaction

# Run database migrations
docker exec layanan_pajak_app php artisan migrate --force --no-interaction

# Rebuild caches
docker exec layanan_pajak_app php artisan config:cache
docker exec layanan_pajak_app php artisan route:cache
docker exec layanan_pajak_app php artisan view:cache
docker exec layanan_pajak_app php artisan event:cache

# Restart queue & scheduler agar pakai kode baru
docker restart layanan_pajak_queue
docker restart layanan_pajak_scheduler

echo "=== Deployment complete ==="
