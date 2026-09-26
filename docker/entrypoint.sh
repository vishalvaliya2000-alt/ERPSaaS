#!/bin/bash
set -e

PORT=${PORT:-80}
echo "Starting ERPSaaS on port ${PORT}..."

# Update Apache port listening
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/:80/:${PORT}/g" /etc/apache2/sites-available/000-default.conf

# Ensure writable storage and cache permissions
mkdir -p /var/www/html/storage/framework/{sessions,views,cache} /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create storage symlink
php artisan storage:link --force || true

# Run database migrations if requested
if [ "${RUN_MIGRATIONS}" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force || echo "Migration encountered an issue, proceeding..."
fi

# Cache configuration, routes, and views for maximum production performance
echo "Caching Laravel configuration and routes..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Hand off to Apache
exec apache2-foreground
