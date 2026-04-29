echo "=== Starting deployment ==="

git pull origin main

# Build dulu
docker compose build

# Jalankan container baru
docker compose up -d

# Pastikan container ready
sleep 5

# Clear cache
docker exec layanan_pajak_app php artisan optimize:clear

# Install deps (kalau memang runtime)
docker exec layanan_pajak_app composer install --no-dev --optimize-autoloader

# Migrate setelah container baru aktif
docker exec layanan_pajak_app php artisan migrate --force

# Cache ulang
docker exec layanan_pajak_app php artisan optimize

echo "=== Deployment complete ==="