#!/bin/bash
set -e

echo "=== Starting deployment ==="

# Pull latest code — langsung ter-reflect di container via volume mount
git pull origin main

# Run database migrations (jika ada)
docker exec layanan_pajak_app php artisan migrate --force --no-interaction

# Clear & rebuild caches agar kode baru ter-load
docker exec layanan_pajak_app php artisan config:cache
docker exec layanan_pajak_app php artisan route:cache
docker exec layanan_pajak_app php artisan view:cache
docker exec layanan_pajak_app php artisan event:cache

# Restart queue & scheduler agar pakai kode baru
docker restart layanan_pajak_queue
docker restart layanan_pajak_scheduler

echo "=== Deployment complete ==="
