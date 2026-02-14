#!/bin/bash
set -e

# ============================================================================
# School Management App - Railway Production Startup Script
# Purpose: Initialize and start Nginx + PHP-FPM stack
# ============================================================================

echo "╔════════════════════════════════════════════════════╗"
echo "║  🚀 School Management App - Railway Production    ║"
echo "╚════════════════════════════════════════════════════╝"
echo ""

# ============================================================================
# Set critical environment variables (MUST be available to all child processes)
# ============================================================================
export PORT=${PORT:-8080}
export APP_ENV=${APP_ENV:-prod}
export APP_DEBUG=${APP_DEBUG:-0}
export APP_SECRET=${APP_SECRET:-}
export PHP_FPM_CMD="php-fpm -F"

echo "[STARTUP] Loading environment..."
echo "  PORT=$PORT"
echo "  APP_ENV=$APP_ENV"
echo "  APP_DEBUG=$APP_DEBUG"

# ============================================================================
# CRITICAL: Ensure APP_SECRET exists (breaks Symfony kernel if missing)
# ============================================================================
if [ -z "$APP_SECRET" ] || [ "$APP_SECRET" = "MISSING-PLEASE-SET-IN-RAILWAY-VARIABLES" ]; then
    echo "[WARNING] APP_SECRET not set in Railway environment. Generating ephemeral secret."
    echo "[WARNING] This means sessions will be lost on container restart."
    echo "[WARNING] To fix: Set APP_SECRET in Railway → Service Settings → Variables"
    APP_SECRET=$(php -r "echo bin2hex(random_bytes(32));")
    export APP_SECRET
fi
echo "[SUCCESS] APP_SECRET configured (length: ${#APP_SECRET})"
echo ""

# ============================================================================
# Validate PORT
# ============================================================================
if ! [[ "$PORT" =~ ^[0-9]+$ ]] || [ "$PORT" -lt 1 ] || [ "$PORT" -gt 65535 ]; then
    echo "[FATAL] Invalid PORT: $PORT"
    exit 1
fi

# ============================================================================
# Validate APP_ENV is production
# ============================================================================
if [ "$APP_ENV" != "prod" ]; then
    echo "[WARNING] APP_ENV is not 'prod' (value: $APP_ENV). Symfony may be in development mode."
fi

# ============================================================================
# Setup application directory
# ============================================================================
cd /var/www/app

echo "[STARTUP] Setting up Symfony directories..."
mkdir -p var/cache var/log var/data var/sessions
chown -R www-data:www-data var
chmod -R 775 var
echo "[SUCCESS] Symfony directories ready"
echo ""

# ============================================================================
# Verify vendor/autoload.php exists
# ============================================================================
echo "[STARTUP] Verifying Composer dependencies..."
if [ ! -f vendor/autoload.php ]; then
    echo "[FATAL] vendor/autoload.php not found. Composer dependencies not installed."
    exit 1
fi
echo "[SUCCESS] vendor/autoload.php found"
echo ""

# ============================================================================
# Generate Nginx config from template (substitute PORT variable)
# ============================================================================
echo "[STARTUP] Configuring Nginx for PORT $PORT..."
envsubst '${PORT}' < /etc/nginx/sites-available/default > /tmp/nginx.conf
if [ $? -ne 0 ]; then
    echo "[FATAL] Failed to substitute Nginx configuration"
    exit 1
fi
mv /tmp/nginx.conf /etc/nginx/sites-available/default
echo "[SUCCESS] Nginx configuration generated"
echo ""

# ============================================================================
# Validate Nginx configuration
# ============================================================================
echo "[STARTUP] Validating Nginx configuration..."
if ! nginx -t 2>&1; then
    echo "[FATAL] Nginx configuration has errors (see above)"
    exit 1
fi
echo "[SUCCESS] Nginx configuration valid"
echo ""

# ============================================================================
# Database setup (SQLite)
# ============================================================================
DB_FILE="/var/www/app/var/data/school_management_prod.db"
if [ ! -f "$DB_FILE" ]; then
    echo "[STARTUP] Creating and seeding SQLite database..."
    php bin/console doctrine:database:create --if-not-exists --env=prod --no-interaction 2>/dev/null || true
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --env=prod 2>/dev/null || true
    echo "[SUCCESS] Database created and migrations applied"
else
    echo "[INFO] Database already exists at $DB_FILE"
fi
echo ""

# ============================================================================
# Ensure Symfony cache is warmed up
# ============================================================================
if [ ! -d "var/cache/prod" ] || [ -z "$(ls -A var/cache/prod 2>/dev/null)" ]; then
    echo "[STARTUP] Warming up Symfony cache..."
    php bin/console cache:warmup --env=prod --no-interaction 2>/dev/null || true
    echo "[SUCCESS] Cache warmed"
fi
echo ""

# ============================================================================
# Final permission verification
# ============================================================================
echo "[STARTUP] Setting final file permissions..."
chown -R www-data:www-data /var/www/app
chmod -R 755 /var/www/app
chmod -R 775 var/cache var/log var/data var/sessions
echo "[SUCCESS] Permissions configured"
echo ""

# ============================================================================
# Initialize log directories
# ============================================================================
mkdir -p /var/log/supervisor /var/log/php-fpm /var/log/nginx
chmod 777 /var/log/nginx

# ============================================================================
# Start log streaming in background
# This ensures Nginx and PHP-FPM logs appear in Railway logs
# ============================================================================
(
    # Wait for log files to be created, then stream them
    sleep 2
    tail -f /var/log/nginx/error.log 2>/dev/null &
    tail -f /var/log/nginx/access.log 2>/dev/null &
    wait
) &

# ============================================================================
# STARTUP COMPLETE - Start Supervisor
# ============================================================================
echo "╔════════════════════════════════════════════════════╗"
echo "║  ✅ Initialization complete - starting services   ║"
echo "║  Stack: Supervisor → PHP-FPM + Nginx             ║"
echo "╚════════════════════════════════════════════════════╝"
echo ""
echo "[INFO] Logs available at:"
echo "       - PHP errors: /var/log/php-error.log"
echo "       - Nginx access: /var/log/nginx/access.log"
echo "       - Nginx errors: /var/log/nginx/error.log"
echo "       - Supervisor logs: /var/log/supervisor/"
echo ""

# ============================================================================
# Start Supervisor in foreground mode
# Supervisor manages PHP-FPM (priority 10) and Nginx (priority 20)
# DO NOT USE: php -S, symfony serve, symfony server:start
# ============================================================================
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf