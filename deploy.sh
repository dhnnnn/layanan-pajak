#!/bin/bash
set -e

echo "=== Starting deployment ==="

# FIX: allow git repo ownership
git config --add safe.directory /var/www

# Pull latest code
git pull origin main

# Stop semua container dengan bersih
docker compose down --remove-orphans

# Tunggu sebentar agar semua resource dilepas
sleep 5

# Bersihkan resource yang masih busy (dangling images, stopped containers)
docker system prune -f

# Tunggu lagi setelah prune
sleep 3

# Start ulang container
docker compose up -d --build

# Tunggu DB healthy
echo "=== Waiting for database ==="
ELAPSED=0
until [ "$(docker inspect -f '{{.State.Health.Status}}' layanan_pajak_db 2>/dev/null)" = "healthy" ]; do
    if [ $ELAPSED -ge 90 ]; then
        echo "ERROR: DB tidak healthy setelah 90s"
        exit 1
    fi
    echo "Waiting for DB... (${ELAPSED}s)"
    sleep 3
    ELAPSED=$((ELAPSED + 3))
done

# Tunggu PHP-FPM ready
echo "=== Waiting for PHP-FPM ==="
ELAPSED=0
until docker exec layanan_pajak_app php artisan --version > /dev/null 2>&1; do
    if [ $ELAPSED -ge 90 ]; then
        echo "ERROR: PHP-FPM tidak ready setelah 90s"
        docker logs layanan_pajak_app --tail 30
        exit 1
    fi
    echo "Waiting for PHP-FPM... (${ELAPSED}s)"
    sleep 3
    ELAPSED=$((ELAPSED + 3))
done

echo "=== App ready, running post-deploy ==="

# Fix permissions
docker exec layanan_pajak_app chmod -R 775 storage bootstrap/cache
docker exec layanan_pajak_app chown -R www-data:www-data storage bootstrap/cache

# Migrate & cache
docker exec layanan_pajak_app php artisan migrate --force --no-interaction
docker exec layanan_pajak_app php artisan optimize

# Reload nginx
docker exec layanan_pajak_nginx nginx -s reload || true

echo "=== Deployment complete ==="
