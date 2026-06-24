FROM dunglas/frankenphp:1.2-php8.3-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    nss-tools \
    git \
    unzip

RUN install-php-extensions \
    pdo_mysql \
    gd \
    zip \
    opcache \
    bcmath

# Copy Composer from official image
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Set environment variables for build
ENV PORT=10000
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_ENV=production
ENV APP_DEBUG=false

# Copy application code
COPY . /app

# Install Composer dependencies (production only)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Install Node, build assets, then remove Node to reduce image size
RUN apk add --no-cache nodejs npm \
    && npm ci \
    && npm run build \
    && apk del nodejs npm

# Set write permissions for Laravel storage and cache directories
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Cache configurations for production performance
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Expose the web server port
EXPOSE 10000

# Run migrations and start FrankenPHP (handles HTTPS proxy and serving Laravel)
CMD sh -c "php artisan storage:link --force && if [ \"\$RUN_MIGRATIONS\" = \"true\" ]; then php artisan migrate --force --no-interaction; fi && exec frankenphp php-server --listen :\$PORT --public-dir public"
```
