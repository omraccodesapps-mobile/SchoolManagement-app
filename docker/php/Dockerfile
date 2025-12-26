# ============================================================================
# Dockerfile for PHP 8.2-FPM with Symfony 7.4 Support
# ============================================================================
# Features:
# - PHP 8.2-FPM
# - SQLite PDO extension for database access
# - intl extension for internationalization
# - zip extension for composer
# - opcache for performance
# - Composer for dependency management
#
# Build: docker build -t school-management:php -f docker/php/Dockerfile .
# ============================================================================

FROM php:8.2-fpm-alpine

# ============================================================================
# Set Working Directory
# ============================================================================
WORKDIR /var/www

# ============================================================================
# Install System Dependencies and PHP Extensions
# ============================================================================

# Install build dependencies
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    sqlite-dev \
    icu-dev \
    libzip-dev

# Install runtime dependencies
RUN apk add --no-cache \
    sqlite-libs \
    icu-libs \
    libzip \
    curl \
    git \
    bash \
    grep \
    sed

# ============================================================================
# Install PHP Extensions
# ============================================================================

# pdo_sqlite: SQLite database support
RUN docker-php-ext-install pdo_sqlite

# intl: Internationalization support
RUN docker-php-ext-install intl

# zip: Archive manipulation
RUN docker-php-ext-install zip

# opcache: PHP opcode cache for performance
RUN docker-php-ext-install opcache

# ============================================================================
# Configure PHP Extensions
# ============================================================================

# Create PHP configuration directory
RUN mkdir -p /usr/local/etc/php/conf.d

# Copy opcache configuration
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Copy application configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

# ============================================================================
# Install Composer
# ============================================================================

# Download composer installer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Make composer executable
RUN chmod +x /usr/local/bin/composer

# Set composer to use interactive mode
ENV COMPOSER_ALLOW_SUPERUSER=1

# ============================================================================
# Create Application User
# ============================================================================

# Create a non-root user for running PHP
RUN addgroup -g 1000 -S app && \
    adduser -u 1000 -S app -G app

# Set ownership of working directory
RUN chown -R app:app /var/www

# ============================================================================
# Health Check
# ============================================================================

# Add health check script
RUN echo '#!/bin/sh' > /healthcheck.sh && \
    echo 'php-fpm -t || exit 1' >> /healthcheck.sh && \
    chmod +x /healthcheck.sh

HEALTHCHECK --interval=10s --timeout=5s --retries=3 \
    CMD /healthcheck.sh

# ============================================================================
# Cleanup Build Dependencies
# ============================================================================

RUN apk del .build-deps

# ============================================================================
# Set User Context
# ============================================================================

USER app

# ============================================================================
# Expose Port
# ============================================================================

EXPOSE 9000

# ============================================================================
# Entry Point
# ============================================================================

CMD ["php-fpm"]
