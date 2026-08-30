# syntax=docker/dockerfile:1

# --- Stage 1: install PHP dependencies ---
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

# --- Stage 2: build frontend assets (Tailwind/Vite) ---
# Tailwind's @source directives in resources/css/app.css point into vendor/,
# so the vendor folder needs to exist before running the Vite build.
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js ./
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# --- Stage 3: runtime image ---
FROM php:8.4-cli-alpine

RUN apk add --no-cache sqlite sqlite-dev \
    && docker-php-ext-install pdo pdo_sqlite bcmath

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p database storage/framework/cache/data storage/framework/sessions storage/framework/testing storage/framework/views storage/logs \
    && touch database/database.sqlite \
    && chown -R www-data:www-data /var/www/html

ENV APP_ENV=production \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/var/www/html/database/database.sqlite \
    QUEUE_CONNECTION=database \
    SESSION_DRIVER=database \
    CACHE_STORE=database

EXPOSE 8000

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
