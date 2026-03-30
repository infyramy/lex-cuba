#!/bin/sh
set -e

echo "=== CORRAD API Starting ==="

# Wait for database to be ready
echo "Waiting for database..."
until php artisan db:monitor --databases=pgsql 2>/dev/null; do
  sleep 2
done
echo "Database is ready."

# Run migrations
echo "Running migrations..."
php artisan migrate --force
echo "Migrations complete."

# Seed on first run — use raw psql count, reliable across all postgres versions
USER_COUNT=$(php artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null | tail -1 | tr -d '[:space:]')
if [ "$USER_COUNT" = "0" ] || [ -z "$USER_COUNT" ]; then
  echo "First run detected — seeding database..."
  php artisan db:seed --force
  echo "Seeding complete."
else
  echo "Users exist ($USER_COUNT) — skipping seed."
fi

# Create storage symlink
php artisan storage:link 2>/dev/null || true

# Clear any stale cache then rebuild
php artisan config:clear
php artisan config:cache
php artisan route:cache

echo "=== CORRAD API Ready ==="

exec "$@"
