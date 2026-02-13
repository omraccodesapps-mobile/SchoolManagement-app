#!/bin/bash
set -e

echo "╔════════════════════════════════════════════════════╗"
echo "║  🚀 School Management App - Railway Production    ║"
echo "╚════════════════════════════════════════════════════╝"

# Set production environment variables
export PORT=${PORT:-8080}
export APP_ENV=${APP_ENV:-prod}
export APP_DEBUG=${APP_DEBUG:-0}
export APP_SECRET=${APP_SECRET:-}

echo "📡 Port: $PORT"
echo "🔧 Environment: $APP_ENV"

# Validate PORT is a valid number
if ! [[ "$PORT" =~ ^[0-9]+$ ]] || [ "$PORT" -lt 1 ] || [ "$PORT" -gt 65535 ]; then
    echo "❌ Invalid PORT: $PORT"
    exit 1
fi

# Set PHP-FPM command
export PHP_FPM_CMD="php-fpm -F"

# Create socket directory for PHP-FPM
mkdir -p /run
chown www-data:www-data /run

# Setup Symfony directories
cd /var/www/app

echo "📁 Setting up Symfony directories..."
mkdir -p var/cache var/log var/data var/sessions
chown -R www-data:www-data var
chmod -R 775 var

# Generate Nginx config from template
echo "⚙️  Configuring Nginx for PORT $PORT..."
envsubst '${PORT}' < /etc/nginx/sites-available/default > /tmp/nginx.conf && \
    mv /tmp/nginx.conf /etc/nginx/sites-available/default

# Validate Nginx config
echo "🧪 Validating Nginx configuration..."
if ! nginx -t 2>&1 | grep -q "successful"; then
    echo "❌ Nginx configuration error:"
    nginx -t
    exit 1
fi
echo "✅ Nginx config is valid"

# Validate Symfony configuration
echo "🔍 Validating Symfony configuration..."
if ! php bin/console config:validate --env=prod 2>&1; then
    echo "⚠️  Symfony config has warnings, continuing..."
fi

# Database migrations (already run in Dockerfile, but check again)
if [ -f "bin/console" ] && [ -f "var/data/school_management_prod.db" ] 2>/dev/null; then
    echo "🗄️  Checking database migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --env=prod 2>/dev/null || true
fi

# Ensure cache is warmed up (done in Dockerfile, but verify)
if [ ! -d "var/cache/prod" ] || [ -z "$(ls -A var/cache/prod)" ]; then
    echo "🔥 Warming up Symfony cache (should have been done in build)..."
    php bin/console cache:warmup --env=prod
fi

# Fix permissions one final time
echo "🔐 Setting file permissions..."
chown -R www-data:www-data /var/www/app
chmod -R 755 /var/www/app
chmod -R 775 var/cache var/log var/data var/sessions

# Log rotation setup
echo "📝 Setting up log management..."
mkdir -p /var/log/supervisor
touch /var/log/php-fpm.log /var/log/php-error.log /var/log/php-access.log
chown -R www-data:www-data /var/log/php-*.log

echo ""
echo "╔════════════════════════════════════════════════════╗"
echo "║  ✅ Initialization complete - starting services   ║"
echo "╚════════════════════════════════════════════════════╝"
echo ""

# Start supervisor (manages PHP-FPM and Nginx)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf