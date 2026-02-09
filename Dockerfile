# Build stage
FROM php:8.2-fpm-alpine AS builder

WORKDIR /app

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    bash \
    zlib-dev \
    libzip-dev

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_sqlite \
    zip

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
    bash

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_sqlite \
    zip

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
