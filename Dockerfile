# Build stage
FROM php:8.2-fpm-alpine AS builder

WORKDIR /app

# Install system dependencies for Composer and build
RUN apk add --no-cache \
    git \
    curl \
    bash \
    build-base \
    zlib-dev \
    libzip-dev \
    icu-dev

# Configure PHP extensions (already included in base image)
RUN docker-php-ext-install \
    zip \
    intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --prefer-dist --no-progress --no-interaction --no-dev --optimize-autoloader

# Copy application
COPY . .

# Copy and make build script executable
COPY build.sh build.sh
RUN chmod +x build.sh

# Runtime stage
FROM php:8.2-fpm-alpine

WORKDIR /app

# Install system dependencies and intl extension
RUN apk add --no-cache \
    curl \
    bash \
    sqlite-libs \
    libzip \
    icu-libs \
    icu-dev && \
    docker-php-ext-install intl

# Copy from builder
COPY --from=builder /app /app

# Create necessary directories and set permissions atomically
RUN mkdir -p /app/var/data /app/var/cache /app/var/log && \
    mkdir -p /app/public && \
    chown -R www-data:www-data /app && \
    chmod -R 755 /app && \
    chmod -R 775 /app/var && \
    chmod +x /app/build.sh

# Initialize database as root
RUN APP_ENV=prod APP_DEBUG=false DATABASE_URL="sqlite:///%kernel.project_dir%/var/data/school.db" \
    bash -c 'set -e; \
    echo "Running migrations..."; \
    php bin/console doctrine:migrations:migrate --no-interaction 2>&1; \
    echo "Warming cache..."; \
    php bin/console cache:warmup --env=prod 2>&1; \
    echo "Database initialization complete"; \
    chown -R www-data:www-data /app/var'

# Install dumb-init for signal handling
RUN apk add --no-cache dumb-init

USER www-data

WORKDIR /app

EXPOSE 8080

# Use dumb-init to handle signals properly
ENTRYPOINT ["dumb-init", "--"]
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
