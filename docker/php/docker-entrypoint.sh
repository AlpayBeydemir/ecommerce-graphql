#!/bin/sh
set -e

echo "Starting Laravel application setup..."

# Wait for database to be ready
echo "Waiting for database connection..."
until php -r "new PDO(getenv('DB_CONNECTION').':host='.getenv('DB_HOST').';port='.getenv('DB_PORT').';dbname='.getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" > /dev/null 2>&1; do
    echo "Database is unavailable - sleeping"
    sleep 2
done

echo "Database is ready!"

# Run migrations
echo "Running database migrations..."
php artisan migrate --force 2>&1 || echo "Migrations may have already been run"

# Install Passport (creates encryption keys if they don't exist)
echo "Setting up Laravel Passport..."
php artisan passport:keys --force 2>/dev/null || true

# Create personal access client if it doesn't exist
echo "Checking Passport clients..."
php artisan passport:client --personal --name="EcommerceAPI" --no-interaction 2>/dev/null || echo "Personal access client already exists"

# Seed database if needed (optional - can be commented out for production)
if [ "$APP_ENV" = "local" ] || [ "$APP_ENV" = "development" ]; then
    echo "Seeding database..."
    php artisan db:seed --force || echo "Seeding skipped or already completed"
fi

# Clear and cache config
echo "Optimizing application..."
php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache

echo "Application setup completed!"

# Execute the main command (php-fpm)
exec "$@"
