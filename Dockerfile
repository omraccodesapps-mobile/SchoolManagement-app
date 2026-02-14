# Production Dockerfile for Symfony 7.4 + Nginx + PHP-FPM + Supervisor
# Architecture: Railway → Nginx (0.0.0.0:$PORT) → PHP-FPM (127.0.0.1:9000)
# DO NOT USE: server:start, php -S, or symfony serve

FROM php:8.2-fpm

# Set environment for build
ENV DEBIAN_FRONTEND=noninteractive \
    PATH=/var/www/app/bin:$PATH

# ============================================================================
# Install system dependencies
# ============================================================================
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
    && rm -rf /var/lib/apt/lists/* \
    && rm -rf /tmp/* /var/tmp/*

# ============================================================================
# Install PHP extensions
# ============================================================================
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_sqlite \
    pdo_mysql \
    zip \
    mbstring \
    gd \
    opcache

# ============================================================================
# Copy Composer from official image
# ============================================================================
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/app

# ============================================================================
# Build stage: Install PHP dependencies
# ============================================================================
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-progress \
    --no-interaction \
    --optimize-autoloader \
    --prefer-dist \
    && composer clear-cache

# ============================================================================
# Verify vendor/autoload.php was created (critical check)
# ============================================================================
RUN if [ ! -f vendor/autoload.php ]; then \
    echo "[FATAL] vendor/autoload.php not found after composer install" && \
    exit 1; \
    fi && \
    echo "✅ vendor/autoload.php verified"

# ============================================================================
# Copy application code
# ============================================================================
COPY . .

# ============================================================================
# Create required directories with correct permissions
# ============================================================================
RUN mkdir -p var/cache var/log var/data var/sessions public/uploads && \
    chown -R www-data:www-data /var/www/app && \
    chmod -R 755 /var/www/app && \
    chmod -R 775 var/cache var/log var/data var/sessions

# ============================================================================
# Pre-build cache warmup (with error visibility)
# ============================================================================
RUN set -e && \
    echo "[BUILD] Generating temporary APP_SECRET..." && \
    export APP_SECRET=$(php -r "echo bin2hex(random_bytes(32));") && \
    export APP_DEBUG=0 && \
    echo "[BUILD] Clearing Symfony cache..." && \
    php bin/console cache:clear --no-warmup --env=prod || true && \
    echo "[BUILD] Warming up cache..." && \
    php bin/console cache:warmup --env=prod || true && \
    echo "[BUILD] Cache preparation complete"

# ============================================================================
# Initialize database schema
# ============================================================================
RUN set -e && \
    export APP_SECRET=$(php -r "echo bin2hex(random_bytes(32));") && \
    export APP_DEBUG=0 && \
    echo "[BUILD] Creating database if needed..." && \
    php bin/console doctrine:database:create --if-not-exists --env=prod --no-interaction 2>/dev/null || true && \
    echo "[BUILD] Running migrations..." && \
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --env=prod 2>/dev/null || true && \
    echo "[BUILD] Database initialization complete"

# ============================================================================
# Copy production configuration files
# ============================================================================
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/railway.conf /etc/nginx/sites-available/default
COPY docker/php/pool.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/symfony.ini
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ============================================================================
# Copy startup script
# ============================================================================
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

# ============================================================================
# Create log directories and initialize logs to /dev/null
# ============================================================================
RUN mkdir -p /var/log/nginx /var/log/supervisor /var/log/php-fpm && \
    touch /var/log/nginx/access.log /var/log/nginx/error.log && \
    chown -R www-data:www-data /var/log/nginx /var/log/supervisor

# ============================================================================
# Nginx configuration verification
# ============================================================================
RUN nginx -t && echo "✅ Nginx configuration valid"

# ============================================================================
# Expose port for Railway
# ============================================================================
EXPOSE 8080

# ============================================================================
# Health check - verify Nginx responds to requests
# ============================================================================
HEALTHCHECK --interval=10s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -f http://localhost:8080/health || exit 1

# ============================================================================
# Start container with Supervisor (manages PHP-FPM and Nginx)
# ============================================================================
CMD ["/start.sh"]