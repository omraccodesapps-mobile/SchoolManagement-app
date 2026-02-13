FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
        nginx git unzip libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libzip-dev zip curl sqlite3 libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite pdo_mysql zip mbstring gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

COPY . .
RUN composer run-script post-install-cmd --no-interaction || true

RUN mkdir -p var/cache var/log var/data && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 775 var/cache var/log var/data

COPY ./docker/nginx/default.conf /etc/nginx/conf.d/default.conf.template

COPY ./docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]