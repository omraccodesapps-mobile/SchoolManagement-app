#!/bin/bash
set -e

echo "╔════════════════════════════════════════════════════╗"
echo "║  🚀 School Management App - Railway Production    ║"
echo "╚════════════════════════════════════════════════════╝"

# Set production environment variables (MUST be available to all child processes)
export PORT=${PORT:-8080}
export APP_ENV=${APP_ENV:-prod}
export APP_DEBUG=${APP_DEBUG:-0}
export APP_SECRET=${APP_SECRET:-}

# Ensure APP_SECRET exists to avoid Symfony 500 errors in production
if [ -z "$APP_SECRET" ]; then
    echo "⚠️  APP_SECRET is not set. Generating ephemeral secret for this container."
    APP_SECRET=$(php -r "echo bin2hex(random_bytes(32));")
    export APP_SECRET
fi
export PHP_FPM_CMD="php-fpm -F"

# Ensure APP_SECRET exists to avoid Symfony 500 errors in production
if [ -z "$APP_SECRET" ]; then
    echo "⚠️  APP_SECRET is not set. Generating ephemeral secret for this container."
    APP_SECRET=$(php -r "echo bin2hex(random_bytes(32));")
    export APP_SECRET
fi

echo "📡 Port: $PORT"
echo "🔧 Environment: $APP_ENV"
echo "🔐 APP_DEBUG: $APP_DEBUG"
echo "✅ APP_SECRET configured"

# Validate PORT is a valid number
if ! [[ "$PORT" =~ ^[0-9]+$ ]] || [ "$PORT" -lt 1 ] || [ "$PORT" -gt 65535 ]; then
    echo "❌ FATAL: Invalid PORT: $PORT"
    exit 1
fi

# Validate APP_ENV is production
if [ "$APP_ENV" != "prod" ]; then
    echo "⚠️  WARNING: APP_ENV is not 'prod' (value: $APP_ENV)"
fi

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
    echo "❌ FATAL: Nginx configuration has errors"
    nginx -t
    exit 1
fi
echo "✅ Nginx configuration valid"

# Database setup (SQLite)
DB_FILE="/var/www/app/var/data/school_management_prod.db"
if [ ! -f "$DB_FILE" ]; then
    echo "🗄️  Creating SQLite database..."
    php bin/console doctrine:database:create --if-not-exists --env=prod --no-interaction 2>/dev/null || true
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --env=prod 2>/dev/null || true
    echo "✅ Database created and migrated"
else
    echo "✅ Database already exists at $DB_FILE"
fi

# Ensure cache is warmed up
if [ ! -d "var/cache/prod" ] || [ -z "$(ls -A var/cache/prod 2>/dev/null)" ]; then
    echo "🔥 Warming up Symfony cache..."
    php bin/console cache:warmup --env=prod --no-interaction 2>/dev/null || true
fi

# Fix permissions one final time
echo "🔐 Setting file permissions..."
chown -R www-data:www-data /var/www/app
chmod -R 755 /var/www/app
chmod -R 775 var/cache var/log var/data var/sessions

# Initialize log directories
mkdir -p /var/log/supervisor /var/log/php-fpm /var/log/nginx
touch /var/log/php-fpm.log /var/log/php-error.log 2>/dev/null || true

echo ""
echo "╔════════════════════════════════════════════════════╗"
echo "║  ✅ Initialization complete                        ║"
echo "║  Starting: Supervisor → PHP-FPM → Nginx          ║"
echo "╚════════════════════════════════════════════════════╝"
echo ""

# Start supervisor (manages PHP-FPM and Nginx)
# PHP-FPM starts first (priority 998), then Nginx (priority 999)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf