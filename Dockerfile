# Build stage
FROM php:8.2-fpm-alpine AS builder

WORKDIR /app

# Install build dependencies and system libraries
RUN apk add --no-cache \
    git \
    curl \
    bash \
    build-base \
    pkgconf \
    zlib-dev \
    libzip-dev \
    sqlite-dev \
    oniguruma-dev

# Install PHP extensions
RUN docker-php-ext-install \
    zip \
    pdo_sqlite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --prefer-dist --no-progress --no-interaction --no-dev --optimize-autoloader

# Copy application
COPY . .

# Copy build script and make it executable
COPY build.sh build.sh
RUN chmod +x build.sh

# Runtime stage
FROM php:8.2-fpm-alpine

WORKDIR /app

# Install runtime dependencies
RUN apk add --no-cache \
    curl \
    bash \
    sqlite-libs \
    libzip

# Copy from builder
COPY --from=builder /app /app

# Set permissions
RUN chown -R www-data:www-data /app && \
    chmod -R 755 /app/public && \
    chmod -R 775 /app/var

# Install dumb-init for signal handling
RUN apk add --no-cache dumb-init

USER www-data

WORKDIR /app/public

EXPOSE 8080

# Use dumb-init to handle signals properly
ENTRYPOINT ["dumb-init", "--"]
CMD ["php", "-S", "0.0.0.0:8080"]
