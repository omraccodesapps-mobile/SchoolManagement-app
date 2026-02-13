#!/bin/bash
set -e

echo "🚀 Starting Railway deployment..."

export PORT=${PORT:-8080}
echo "📡 Using PORT: $PORT"

envsubst '${PORT}' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf
echo "✅ Nginx config generated for port $PORT"

mkdir -p /var/www/html/var/cache /var/www/html/var/log /var/www/html/var/data
chown -R www-data:www-data /var/www/html/var
chmod -R 775 /var/www/html/var

echo "🔥 Clearing Symfony cache..."
php bin/console cache:clear --no-warmup --env=prod
echo "🔥 Warming up Symfony cache..."
php bin/console cache:warmup --env=prod

if [ -f "bin/console" ]; then
    echo "🗄️  Running database migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || echo "⚠️  No migrations to run"
fi

chown -R www-data:www-data /var/www/html/var
chmod -R 775 /var/www/html/var

echo "🐘 Starting PHP-FPM..."
php-fpm -D

sleep 2
if ! pgrep -x "php-fpm" > /dev/null; then
    echo "❌ PHP-FPM failed to start!"
    exit 1
fi
echo "✅ PHP-FPM is running"

echo "🧪 Testing Nginx configuration..."
nginx -t

echo "🌐 Starting Nginx on port $PORT..."
nginx -g "daemon off;"