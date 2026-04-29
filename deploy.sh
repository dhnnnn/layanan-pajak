#!/bin/bash
set -e

echo "=== Starting deployment ==="

# FIX: allow git repo ownership
git config --add safe.directory /var/www

# Pull latest code (kode langsung di-mount via volume, tidak perlu rebuild image)
git pull origin main

# Restart container agar pakai kode terbaru (down dulu agar tidak error)
docker compose down
docker compose up -d

# Tunggu DB healthy dulu
echo "=== Waiting for database to be healthy ==="
ELAPSED=0
until [ "$(docker inspect -f '{{.State.Health.Status}}' layanan_pajak_db 2>/dev/null)" = "healthy" ]; do
    if [ $ELAPSED -ge 60 ]; then
        echo "ERROR: Database tidak healthy setelah 60s"
        exit 1
    fi
    echo "Waiting for DB... (${ELAPSED}s)"
    sleep 3
    ELAPSED=$((ELAPSED + 3))
done

# Tunggu PHP-FPM ready
echo "=== Waiting for PHP-FPM to be ready ==="
ELAPSED=0
until docker exec layanan_pajak_app php artisan --version > /dev/null 2>&1; do
    if [ $ELAPSED -ge 60 ]; then
        echo "ERROR: PHP-FPM tidak ready setelah 60s"
        docker logs layanan_pajak_app --tail 20
        exit 1
    fi
    echo "Waiting for PHP-FPM... (${ELAPSED}s)"
    sleep 3
    ELAPSED=$((ELAPSED + 3))
done

echo "=== App is ready ==="

# Fix storage permissions
docker exec layanan_pajak_app chmod -R 775 storage bootstrap/cache
docker exec layanan_pajak_app chown -R www-data:www-data storage bootstrap/cache

# Install dependencies
docker exec layanan_pajak_app composer install --no-dev --optimize-autoloader --no-interaction

# Run database migrations
docker exec layanan_pajak_app php artisan migrate --force --no-interaction

# Rebuild semua cache
docker exec layanan_pajak_app php artisan optimize

# Reload nginx agar koneksi ke app fresh
docker exec layanan_pajak_nginx nginx -s reload

echo "=== Deployment complete ==="
