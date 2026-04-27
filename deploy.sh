#!/bin/bash
set -e

echo "=== Starting deployment ==="

# Pull latest changes from main
git pull origin main

# Rebuild & restart Docker containers
docker compose build app
docker compose up -d

# Wait for app container to be ready
echo "Waiting for app container..."
sleep 5

# Run database migrations inside container
docker exec layanan_pajak_app php artisan migrate --force --no-interaction

# Clear & rebuild caches
docker exec layanan_pajak_app php artisan config:cache
docker exec layanan_pajak_app php artisan route:cache
docker exec layanan_pajak_app php artisan view:cache
docker exec layanan_pajak_app php artisan event:cache

# Restart queue worker to pick up new code
docker restart layanan_pajak_queue
docker restart layanan_pajak_scheduler

echo "=== Deployment complete ==="
