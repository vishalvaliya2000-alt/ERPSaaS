#!/bin/bash
set -e

PORT=${PORT:-80}
echo "Starting ERPSaaS on port ${PORT}..."

# Update Apache port listening
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/:80/:${PORT}/g" /etc/apache2/sites-available/000-default.conf

# 1. Setup .env file
if [ ! -f /var/www/html/.env ]; then
    if [ -f /var/www/html/.env.example ]; then
        echo "Creating .env from .env.example..."
        cp /var/www/html/.env.example /var/www/html/.env
    else
        touch /var/www/html/.env
    fi
fi

# 2. Production defaults for rock-solid stability
export APP_NAME=${APP_NAME:-ERPSaaS}
export APP_ENV=${APP_ENV:-production}
export APP_DEBUG=${APP_DEBUG:-false}
export SESSION_DRIVER=${SESSION_DRIVER:-file}
export CACHE_STORE=${CACHE_STORE:-file}
export QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}
export LOG_CHANNEL=${LOG_CHANNEL:-stderr}

# Ensure key configuration exists in .env
grep -q "^APP_NAME=" /var/www/html/.env && sed -i "s|^APP_NAME=.*|APP_NAME=\"${APP_NAME}\"|g" /var/www/html/.env || echo "APP_NAME=\"${APP_NAME}\"" >> /var/www/html/.env
grep -q "^SESSION_DRIVER=" /var/www/html/.env && sed -i "s|^SESSION_DRIVER=.*|SESSION_DRIVER=${SESSION_DRIVER}|g" /var/www/html/.env || echo "SESSION_DRIVER=${SESSION_DRIVER}" >> /var/www/html/.env
grep -q "^CACHE_STORE=" /var/www/html/.env && sed -i "s|^CACHE_STORE=.*|CACHE_STORE=${CACHE_STORE}|g" /var/www/html/.env || echo "CACHE_STORE=${CACHE_STORE}" >> /var/www/html/.env
grep -q "^QUEUE_CONNECTION=" /var/www/html/.env && sed -i "s|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=${QUEUE_CONNECTION}|g" /var/www/html/.env || echo "QUEUE_CONNECTION=${QUEUE_CONNECTION}" >> /var/www/html/.env
grep -q "^LOG_CHANNEL=" /var/www/html/.env && sed -i "s|^LOG_CHANNEL=.*|LOG_CHANNEL=${LOG_CHANNEL}|g" /var/www/html/.env || echo "LOG_CHANNEL=${LOG_CHANNEL}" >> /var/www/html/.env

# 3. Application Key (APP_KEY)
if [ -n "$APP_KEY" ]; then
    echo "Configuring APP_KEY from environment..."
    grep -q "^APP_KEY=" /var/www/html/.env && sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|g" /var/www/html/.env || echo "APP_KEY=${APP_KEY}" >> /var/www/html/.env
else
    if ! grep -q "^APP_KEY=base64:" /var/www/html/.env 2>/dev/null; then
        echo "Generating new APP_KEY..."
        php artisan key:generate --force
    fi
    export APP_KEY=$(grep '^APP_KEY=' /var/www/html/.env | cut -d '=' -f2- | tr -d '\r')
fi

# 4. Database configuration (Injected via Render Dashboard environment variables)
export DB_CONNECTION=${DB_CONNECTION:-mysql}
export DB_PORT=${DB_PORT:-3306}
export MYSQL_ATTR_SSL_CA=${MYSQL_ATTR_SSL_CA:-/etc/ssl/certs/isrgrootx1.pem}

echo "Configuring database connection from environment..."
grep -q "^DB_CONNECTION=" /var/www/html/.env && sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=${DB_CONNECTION}|g" /var/www/html/.env || echo "DB_CONNECTION=${DB_CONNECTION}" >> /var/www/html/.env
[ -n "$DB_HOST" ] && { grep -q "^DB_HOST=" /var/www/html/.env && sed -i "s|^DB_HOST=.*|DB_HOST=${DB_HOST}|g" /var/www/html/.env || echo "DB_HOST=${DB_HOST}" >> /var/www/html/.env; }
[ -n "$DB_PORT" ] && { grep -q "^DB_PORT=" /var/www/html/.env && sed -i "s|^DB_PORT=.*|DB_PORT=${DB_PORT}|g" /var/www/html/.env || echo "DB_PORT=${DB_PORT}" >> /var/www/html/.env; }
[ -n "$DB_DATABASE" ] && { grep -q "^DB_DATABASE=" /var/www/html/.env && sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_DATABASE}|g" /var/www/html/.env || echo "DB_DATABASE=${DB_DATABASE}" >> /var/www/html/.env; }
[ -n "$DB_USERNAME" ] && { grep -q "^DB_USERNAME=" /var/www/html/.env && sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USERNAME}|g" /var/www/html/.env || echo "DB_USERNAME=${DB_USERNAME}" >> /var/www/html/.env; }
[ -n "$DB_PASSWORD" ] && { grep -q "^DB_PASSWORD=" /var/www/html/.env && sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|g" /var/www/html/.env || echo "DB_PASSWORD=${DB_PASSWORD}" >> /var/www/html/.env; }
[ -n "$MYSQL_ATTR_SSL_CA" ] && { grep -q "^MYSQL_ATTR_SSL_CA=" /var/www/html/.env && sed -i "s|^MYSQL_ATTR_SSL_CA=.*|MYSQL_ATTR_SSL_CA=${MYSQL_ATTR_SSL_CA}|g" /var/www/html/.env || echo "MYSQL_ATTR_SSL_CA=${MYSQL_ATTR_SSL_CA}" >> /var/www/html/.env; }

# 5. Storage & database permissions
mkdir -p /var/www/html/storage/framework/{sessions,views,cache,data} /var/www/html/storage/logs /var/www/html/database
touch /var/www/html/database/database.sqlite
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# 6. Storage symlink
php artisan storage:link --force || true

# 7. Database migrations & seeding
echo "Running database migrations..."
php artisan migrate --force || echo "Notice: Database migration failed or database unreachable, continuing..."

echo "Seeding initial admin and catalog data..."
php artisan db:seed --force || echo "Notice: Seeding skipped or already populated."

# 8. Production caching
echo "Optimizing application cache..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# 9. Start Apache in foreground
exec apache2-foreground
