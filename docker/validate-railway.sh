#!/bin/bash
# Railway Production Validation Script
# Run this in Railway Shell to verify the entire stack is working correctly

set -e

echo "╔════════════════════════════════════════════════════╗"
echo "║   Railway Production Stack Validation              ║"
echo "╚════════════════════════════════════════════════════╝"
echo ""

ERRORS=0
WARNINGS=0
SUCCESS=0

# Helper functions
check_success() {
    echo "✅ $1"
    ((SUCCESS++))
}

check_warning() {
    echo "⚠️  $1"
    ((WARNINGS++))
}

check_error() {
    echo "❌ $1"
    ((ERRORS++))
}

# ============================================================================
# 1. Check Environment Variables
# ============================================================================
echo "[1] Checking Environment Variables..."
echo "────────────────────────────────────────"

if [ -z "$APP_SECRET" ]; then
    check_error "APP_SECRET is not set"
else
    if [ "$APP_SECRET" = "MISSING-PLEASE-SET-IN-RAILWAY-VARIABLES" ]; then
        check_error "APP_SECRET is still placeholder value"
    elif [ ${#APP_SECRET} -ne 64 ]; then
        check_warning "APP_SECRET is ${#APP_SECRET} characters (should be 64 for 32 bytes)"
    else
        check_success "APP_SECRET is set correctly (${#APP_SECRET} chars)"
    fi
fi

if [ "$APP_ENV" = "prod" ]; then
    check_success "APP_ENV=prod (correct for production)"
else
    check_error "APP_ENV=$APP_ENV (should be 'prod')"
fi

if [ "$APP_DEBUG" = "0" ]; then
    check_success "APP_DEBUG=0 (debug disabled)"
else
    check_warning "APP_DEBUG=$APP_DEBUG (should be 0 for production)"
fi

if [ -z "$PORT" ]; then
    check_error "PORT is not set"
elif [ "$PORT" = "8080" ]; then
    check_success "PORT=$PORT (standard Railway port)"
else
    check_warning "PORT=$PORT (may be non-standard)"
fi
echo ""

# ============================================================================
# 2. Check Application Files
# ============================================================================
echo "[2] Checking Application Files..."
echo "────────────────────────────────────────"

cd /var/www/app

# Check vendor/autoload.php
if [ -f vendor/autoload.php ]; then
    check_success "vendor/autoload.php exists"
else
    check_error "vendor/autoload.php NOT FOUND (Composer dependencies not installed)"
fi

# Check Symfony entry point
if [ -f public/index.php ]; then
    check_success "public/index.php exists (Symfony entry point)"
else
    check_error "public/index.php NOT FOUND"
fi

# Check bin/console
if [ -f bin/console ]; then
    check_success "bin/console exists (Symfony console)"
else
    check_error "bin/console NOT FOUND"
fi

# Check .env files
if [ -f .env ]; then
    check_success ".env exists"
else
    check_warning ".env not found (using env variables)"
fi

if [ -f .env.railway ]; then
    check_success ".env.railway exists"
else
    check_warning ".env.railway not found"
fi
echo ""

# ============================================================================
# 3. Check Cache and Logs Directories
# ============================================================================
echo "[3] Checking Cache and Log Directories..."
echo "────────────────────────────────────────"

# Check var/cache
if [ -d var/cache/prod ]; then
    CACHE_FILES=$(find var/cache/prod -type f 2>/dev/null | wc -l)
    if [ "$CACHE_FILES" -gt 0 ]; then
        check_success "var/cache/prod exists with $CACHE_FILES files"
    else
        check_warning "var/cache/prod exists but is empty (cache not warmed)"
    fi
else
    check_error "var/cache/prod directory not found"
fi

# Check var/log
if [ -d var/log ]; then
    check_success "var/log directory exists"
else
    check_warning "var/log directory not found"
fi

# Check var/data
if [ -d var/data ]; then
    check_success "var/data directory exists"
else
    check_warning "var/data directory not found"
fi

# Check session directory
if [ -d var/sessions ]; then
    check_success "var/sessions directory exists"
else
    check_warning "var/sessions directory not found"
fi
echo ""

# ============================================================================
# 4. Check Database
# ============================================================================
echo "[4] Checking Database..."
echo "────────────────────────────────────────"

DB_FILE="var/data/school_management_prod.db"
if [ -f "$DB_FILE" ]; then
    DB_SIZE=$(du -h "$DB_FILE" | cut -f1)
    check_success "SQLite database exists ($DB_SIZE)"
    
    # Try to validate database
    if command -v sqlite3 &> /dev/null; then
        if sqlite3 "$DB_FILE" ".tables" &>/dev/null; then
            TABLES=$(sqlite3 "$DB_FILE" ".tables" | wc -w)
            check_success "Database is valid with $TABLES tables"
        else
            check_error "Database file is corrupted or locked"
        fi
    else
        check_warning "sqlite3 command not available to validate database"
    fi
else
    check_warning "SQLite database not found (migrations may not have run)"
fi
echo ""

# ============================================================================
# 5. Check Process Status
# ============================================================================
echo "[5] Checking Running Processes..."
echo "────────────────────────────────────────"

# Check Supervisor
if pgrep -f "supervisord" > /dev/null; then
    check_success "Supervisor is running"
else
    check_error "Supervisor NOT running"
fi

# Check PHP-FPM
if pgrep -f "php-fpm" > /dev/null; then
    PHP_FPM_COUNT=$(pgrep -f "php-fpm" | wc -l)
    check_success "PHP-FPM is running ($PHP_FPM_COUNT processes)"
else
    check_error "PHP-FPM NOT running"
fi

# Check Nginx
if pgrep -f "nginx" > /dev/null; then
    NGINX_COUNT=$(pgrep -f "nginx" | wc -l)
    check_success "Nginx is running ($NGINX_COUNT processes)"
else
    check_error "Nginx NOT running"
fi

# Check Supervisor status
if command -v supervisorctl &> /dev/null; then
    echo ""
    echo "  Supervisor process status:"
    supervisorctl status 2>&1 | sed 's/^/    /'
fi
echo ""

# ============================================================================
# 6. Check Network Listeners
# ============================================================================
echo "[6] Checking Network Listeners..."
echo "────────────────────────────────────────"

# Check PHP-FPM socket
if netstat -tlnp 2>/dev/null | grep -q "127.0.0.1:9000"; then
    check_success "PHP-FPM listening on 127.0.0.1:9000"
elif ss -tlnp 2>/dev/null | grep -q "127.0.0.1:9000"; then
    check_success "PHP-FPM listening on 127.0.0.1:9000"
else
    check_error "PHP-FPM socket 127.0.0.1:9000 not listening"
fi

# Check Nginx port
if netstat -tlnp 2>/dev/null | grep -q ":$PORT"; then
    check_success "Nginx listening on 0.0.0.0:$PORT"
elif ss -tlnp 2>/dev/null | grep -q ":$PORT"; then
    check_success "Nginx listening on 0.0.0.0:$PORT"
else
    check_error "Nginx not listening on port $PORT"
fi
echo ""

# ============================================================================
# 7. Check Log Files
# ============================================================================
echo "[7] Checking Log Files..."
echo "────────────────────────────────────────"

# Check nginx error log
if [ -f /var/log/nginx/error.log ]; then
    check_success "/var/log/nginx/error.log exists"
    
    # Check for recent errors
    RECENT_ERRORS=$(grep -i "error" /var/log/nginx/error.log 2>/dev/null | tail -3 || true)
    if [ -n "$RECENT_ERRORS" ]; then
        echo "  Recent nginx errors:"
        echo "$RECENT_ERRORS" | sed 's/^/    /'
    fi
else
    check_warning "/var/log/nginx/error.log not found (Nginx logs to stderr)"
fi

# Check PHP error log
if [ -f /var/log/php-error.log ]; then
    check_success "/var/log/php-error.log exists"
    
    # Check for recent errors
    RECENT_PHP_ERRORS=$(tail -3 /var/log/php-error.log 2>/dev/null || true)
    if [ -n "$RECENT_PHP_ERRORS" ]; then
        echo "  Recent PHP errors:"
        echo "$RECENT_PHP_ERRORS" | sed 's/^/    /'
    fi
else
    check_warning "/var/log/php-error.log not found (PHP logs to stderr)"
fi
echo ""

# ============================================================================
# 8. Check File Permissions
# ============================================================================
echo "[8] Checking File Permissions..."
echo "────────────────────────────────────────"

# Check var directory ownership
VAR_OWNER=$(ls -ld var | awk '{print $3}')
if [ "$VAR_OWNER" = "www-data" ] || [ "$VAR_OWNER" = "1000" ]; then
    check_success "var/ is owned by www-data"
else
    check_warning "var/ is owned by $VAR_OWNER (should be www-data)"
fi

# Check var/cache permissions
VAR_CACHE_PERMS=$(stat -c %a var/cache 2>/dev/null || stat -f %OLp var/cache 2>/dev/null || echo "unknown")
if [ "$VAR_CACHE_PERMS" = "775" ] || [ "$VAR_CACHE_PERMS" = "755" ]; then
    check_success "var/cache has correct permissions ($VAR_CACHE_PERMS)"
else
    check_warning "var/cache permissions are $VAR_CACHE_PERMS (should be 775)"
fi

# Check var/log permissions
VAR_LOG_PERMS=$(stat -c %a var/log 2>/dev/null || stat -f %OLp var/log 2>/dev/null || echo "unknown")
if [ "$VAR_LOG_PERMS" = "775" ] || [ "$VAR_LOG_PERMS" = "755" ]; then
    check_success "var/log has correct permissions ($VAR_LOG_PERMS)"
else
    check_warning "var/log permissions are $VAR_LOG_PERMS (should be 775)"
fi
echo ""

# ============================================================================
# 9. Test HTTP Connectivity
# ============================================================================
echo "[9] Testing HTTP Connectivity..."
echo "────────────────────────────────────────"

# Test health endpoint
if command -v curl &> /dev/null; then
    HEALTH_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:$PORT/health 2>/dev/null || echo "000")
    
    if [ "$HEALTH_RESPONSE" = "200" ]; then
        check_success "Health endpoint returns HTTP 200"
    else
        check_error "Health endpoint returned HTTP $HEALTH_RESPONSE (expected 200)"
    fi
    
    # Test home page
    HOME_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:$PORT/ 2>/dev/null || echo "000")
    
    if [ "$HOME_RESPONSE" = "200" ]; then
        check_success "Home page returns HTTP 200"
    elif [ "$HOME_RESPONSE" = "302" ] || [ "$HOME_RESPONSE" = "301" ]; then
        check_success "Home page returns HTTP $HOME_RESPONSE (redirect)"
    elif [ "$HOME_RESPONSE" = "500" ]; then
        check_error "Home page returns HTTP 500 (server error)"
    else
        check_warning "Home page returns HTTP $HOME_RESPONSE"
    fi
else
    check_warning "curl not available (cannot test HTTP connectivity)"
fi
echo ""

# ============================================================================
# 10. Check Configuration Files
# ============================================================================
echo "[10] Checking Configuration Files..."
echo "────────────────────────────────────────"

# Check nginx configuration
if nginx -t 2>&1 | grep -q "successful"; then
    check_success "Nginx configuration is valid"
else
    check_error "Nginx configuration has errors"
    nginx -t 2>&1 | sed 's/^/    /'
fi

# Check supervisor configuration
if supervisorctl status &>/dev/null; then
    check_success "Supervisor configuration is valid"
else
    check_warning "Supervisor configuration check failed"
fi

# Check PHP configuration
PHP_OPCACHE=$(php -i 2>/dev/null | grep "opcache.enable" | grep -c "1" || echo "0")
if [ "$PHP_OPCACHE" -gt 0 ]; then
    check_success "OPcache is enabled"
else
    check_warning "OPcache is not enabled (performance impact)"
fi
echo ""

# ============================================================================
# Summary
# ============================================================================
echo "╔════════════════════════════════════════════════════╗"
echo "║   Validation Summary                              ║"
echo "╚════════════════════════════════════════════════════╝"
echo ""
echo "✅ Success:  $SUCCESS"
echo "⚠️  Warnings: $WARNINGS"
echo "❌ Errors:   $ERRORS"
echo ""

if [ $ERRORS -eq 0 ]; then
    echo "✅ All critical checks passed! Application is ready."
    exit 0
elif [ $ERRORS -lt 3 ]; then
    echo "⚠️  Some issues found, but application may still work."
    echo "   Review the errors above and fix if needed."
    exit 1
else
    echo "❌ Critical issues found. Application will NOT work."
    echo "   Please fix all errors before proceeding."
    exit 2
fi
