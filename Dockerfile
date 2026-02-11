# Base with PHP-FPM
FROM php:8.2-fpm

# Installer Nginx + extensions pour Symfony
RUN apt-get update && apt-get install -y \
        nginx git unzip libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libzip-dev zip curl \
    && docker-php-ext-install pdo pdo_mysql zip mbstring gd

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copier le code
COPY . .

# Installer dépendances
RUN composer install --no-dev --optimize-autoloader

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Config Nginx
COPY ./docker/nginx/default.conf /etc/nginx/sites-available/default

# Script de démarrage
COPY ./docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
