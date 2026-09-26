# -------------------------------------------------------------
# Stage 1: Build frontend assets (Vite / Tailwind CSS)
# -------------------------------------------------------------
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci || npm install
COPY . .
RUN npm run build

# -------------------------------------------------------------
# Stage 2: Production PHP runtime
# -------------------------------------------------------------
FROM php:8.4-apache

# Install system dependencies & PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    git \
    curl \
    sqlite3 \
    libsqlite3-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache Rewrite Module
RUN a2enmod rewrite

# Copy Apache VirtualHost configuration & TiDB Root CA Certificate
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/isrgrootx1.pem /etc/ssl/certs/isrgrootx1.pem
RUN update-ca-certificates || true

WORKDIR /var/www/html

# Copy composer files and install dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application source code
COPY . .

# Copy compiled frontend assets from Stage 1
COPY --from=frontend /app/public/build /var/www/html/public/build

# Finish Composer dump-autoload and package discovery
RUN composer dump-autoload --optimize --no-dev

# Ensure permissions and executable scripts
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod +x docker/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker/entrypoint.sh"]
