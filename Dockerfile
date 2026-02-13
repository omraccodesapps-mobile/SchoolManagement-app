# Multi-stage production Dockerfile for Symfony + Nginx + PHP-FPM
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    git \
    unzip \
    ca-certificates \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libzip-dev \
    zip \
    curl \
    sqlite3 \
    libsqlite3-dev \
    gettext-base \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_sqlite \
    pdo_mysql \
    zip \
    mbstring \
    gd \
    opcache

# Copy Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/app

# Install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-progress \
    --no-interaction \
    --optimize-autoloader \
    --prefer-dist

# Copy application code
COPY . .

# Create required directories with correct permissions
RUN mkdir -p var/cache var/log var/data public/uploads && \
    chown -R www-data:www-data /var/www/app && \
    chmod -R 755 /var/www/app && \
    chmod -R 775 var/cache var/log var/data

# Build stage: warm up cache for production
RUN php bin/console cache:clear --no-warmup --env=prod && \
    php bin/console cache:warmup --env=prod && \
    php bin/console doctrine:database:create --if-not-exists --env=prod 2>/dev/null || true && \
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --env=prod 2>/dev/null || true

# Copy configuration files
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/railway.conf /etc/nginx/sites-available/default
COPY docker/php/pool.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/symfony.ini
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy startup script
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

# Create log directories
RUN mkdir -p /var/log/nginx /var/log/supervisor

# Expose port
EXPOSE 8080

# Health check
HEALTHCHECK --interval=10s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -f http://localhost:8080/ || exit 1

CMD ["/start.sh"]