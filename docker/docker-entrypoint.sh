#!/usr/bin/env bash
set -e

# Wait for Postgres
RETRIES=0
until (nc -z "$DB_HOST" "$DB_PORT") || [ "$RETRIES" -ge 30 ]; do
  echo "Waiting for database ($DB_HOST:$DB_PORT)..."
  RETRIES=$((RETRIES+1))
  sleep 1
done

# Install composer dependencies if vendor not present
if [ ! -d "vendor" ]; then
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Copy .env.example if .env not present
if [ ! -f ".env" ]; then
  cp .env.example .env
  php artisan key:generate
fi

# Run migrations
php artisan migrate --force || true

# Serve the app
php artisan serve --host=0.0.0.0 --port=8000
