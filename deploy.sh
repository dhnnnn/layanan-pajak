#!/bin/bash
set -e

echo "=== Starting deployment ==="

# FIX: allow git repo ownership
git config --add safe.directory /var/www

# Pull latest code
git pull origin main

echo "=== Stopping current containers ==="
# Stop semua container dengan bersih
docker compose down --remove-orphans

# Beri waktu lebih lama agar OS benar-benar melepas volume mount
echo "Waiting for OS to release volume mounts..."
sleep 10

# PERBAIKAN: Hapus 'docker system prune -f' dari sini agar tidak memicu "device busy"

echo "=== Building and starting containers ==="
# Tambahkan sistem RETRY: Jika gagal karena resource busy, dia akan coba lagi sampai 3x
MAX_RETRIES=3
RETRY_COUNT=0
SUCCESS=false

while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
    if docker compose up -d --build; then
        SUCCESS=true
        break
    else
        echo "⚠️ Attempt $((RETRY_COUNT+1)) failed (Mungkin resource masih busy). Retrying in 5 seconds..."
        sleep 5
        RETRY_COUNT=$((RETRY_COUNT+1))
    fi
done

if [ "$SUCCESS" = false ]; then
    echo "❌ ERROR: Gagal menjalankan docker compose up setelah $MAX_RETRIES percobaan."
    exit 1
fi

# Tunggu DB healthy
echo "=== Waiting for database ==="
ELAPSED=0
until [ "$(docker inspect -f '{{.State.Health.Status}}' layanan_pajak_db 2>/dev/null)" = "healthy" ]; do
    if [ $ELAPSED -ge 90 ]; then
        echo "❌ ERROR: DB tidak healthy setelah 90s"
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
        echo "❌ ERROR: PHP-FPM tidak ready setelah 90s"
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

echo "=== Cleaning up unused Docker resources ==="
# PERBAIKAN: Lakukan prune di paling akhir saat aplikasi sudah berjalan dengan aman
# Menggunakan 'image prune' lebih aman daripada 'system prune' untuk production
docker image prune -f

echo "=== Deployment complete ✅ ==="