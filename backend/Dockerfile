FROM php:8.3-cli-alpine

RUN apk add --no-cache \
    postgresql-dev \
    bash \
    curl \
    && docker-php-ext-install pdo pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 8000

CMD ["sh", "-c", "composer install --no-interaction --no-scripts --no-security-blocking --prefer-dist && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache && chmod -R 777 storage bootstrap/cache && composer dump-autoload && php artisan serve --host=0.0.0.0 --port=8000"]
