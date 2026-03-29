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

# Seed on first run (skip if users table has data)
USER_COUNT=$(php artisan db:query "SELECT COUNT(*) as count FROM users" --json 2>/dev/null | grep -o '"count":"[0-9]*"' | grep -o '[0-9]*' || echo "0")
if [ "${USER_COUNT:-0}" = "0" ]; then
  echo "First run detected — seeding database..."
  php artisan db:seed --force
  echo "Seeding complete."
else
  echo "Users exist (${USER_COUNT}) — skipping seed."
fi

# Create storage symlink
php artisan storage:link 2>/dev/null || true

# Cache for performance
php artisan config:cache
php artisan route:cache

echo "=== CORRAD API Ready ==="

exec "$@"
