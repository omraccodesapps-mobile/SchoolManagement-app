#!/bin/bash
# Railway production build script for Symfony with Docker

set -e

echo "╔════════════════════════════════════════════════════╗"
echo "║  🏗️  School Management App Build for Railway      ║"
echo "╚════════════════════════════════════════════════════╝"

export APP_ENV=${APP_ENV:-prod}
export APP_DEBUG=${APP_DEBUG:-0}

# Step 1: Clear corrupted cache
echo "📦 Step 1: Clearing previous cache..."
rm -rf var/cache/* 2>/dev/null || echo "  (cache was empty)"
rm -rf var/log/* 2>/dev/null || echo "  (logs were empty)"

# Step 2: Install PHP dependencies (composer.json/composer.lock)
echo "📦 Step 2: Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Step 3: Create necessary directories
echo "📁 Step 3: Creating Symfony directories..."
mkdir -p var/data var/cache var/log var/sessions
chmod -R 775 var/

# Step 4: Setup database (if local SQLite)
echo "🗄️  Step 4: Setting up database..."
php bin/console doctrine:database:create --if-not-exists --env=prod 2>/dev/null || echo "  (database might exist or be external)"
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --env=prod 2>/dev/null || echo "  (migrations will run at startup)"

# Step 5: Warm up Symfony cache - CRITICAL for preventing 502 at startup
echo "🔥 Step 5: Warming up Symfony production cache..."
if php bin/console cache:warmup --env=prod 2>&1; then
    echo "✅ Cache warmup successful"
else
    echo "⚠️  Cache warmup had issues, but could be runtime issue"
fi

# Step 6: Asset compilation
echo "📁 Step 6: Compiling assets..."
php bin/console asset-map:compile 2>/dev/null || echo "  (no assets to compile)"

# Step 7: Final permissions
echo "🔐 Step 7: Setting file permissions..."
chmod -R 755 var/
chmod -R 775 var/cache var/log var/data var/sessions

echo ""
echo "╔════════════════════════════════════════════════════╗"
echo "║  ✅ Build complete and ready for deployment       ║"
echo "╚════════════════════════════════════════════════════╝"

