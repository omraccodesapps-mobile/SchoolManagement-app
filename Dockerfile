# Build stage
FROM php:8.2-fpm-alpine AS builder

WORKDIR /app

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    mysql-client \
    zlib-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    gd \
    intl \
    zip \
    mbstring

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --prefer-dist --no-progress --no-interaction --no-dev

# Copy application
COPY . .

# Runtime stage
FROM php:8.2-fpm-alpine

WORKDIR /app

# Install runtime dependencies only
RUN apk add --no-cache \
    mysql-client \
    zlib \
    libzip \
    libpng \
    libjpeg-turbo \
    freetype \
    icu

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    gd \
    intl \
    zip \
    mbstring

# Copy from builder
COPY --from=builder /app /app

# Set permissions
RUN chown -R www-data:www-data /app && \
    chmod -R 755 /app/public && \
    chmod -R 775 /app/var

USER www-data

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD php -r "exit(file_exists('/app/var/cache') ? 0 : 1);"

EXPOSE 9000
