#!/bin/bash
set -e

echo "=== Starting deployment ==="

# FIX: allow git repo ownership
git config --add safe.directory /var/www

# Pull latest code
git pull origin main

# Rebuild image baru & restart container (down dulu agar tidak error port conflict)
docker compose down
docker compose up -d --build

# Tunggu container app benar-benar ready (max 90 detik)
echo "=== Waiting for app container to be ready ==="
for i in $(seq 1 30); do
    if docker exec layanan_pajak_app php artisan --version > /dev/null 2>&1; then
        echo "=== App container is ready after ${i}x3 seconds ==="
        break
    fi
    if [ $i -eq 30 ]; then
        echo "=== ERROR: App container not ready after 90 seconds ==="
        exit 1
    fi
    echo "Waiting... ($i/30)"
    sleep 3
done

# Clear old caches
docker exec layanan_pajak_app rm -f bootstrap/cache/config.php
docker exec layanan_pajak_app rm -f bootstrap/cache/packages.php
docker exec layanan_pajak_app rm -f bootstrap/cache/services.php

# Fix storage permissions
docker exec layanan_pajak_app chmod -R 775 storage bootstrap/cache
docker exec layanan_pajak_app chown -R www-data:www-data storage bootstrap/cache

# Install dependencies
docker exec layanan_pajak_app composer install --no-dev --optimize-autoloader --no-interaction

# Run database migrations
docker exec layanan_pajak_app php artisan migrate --force --no-interaction

# Rebuild caches
docker exec layanan_pajak_app php artisan optimize

echo "=== Deployment complete ==="
