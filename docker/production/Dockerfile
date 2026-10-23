# Image produksi EduwGo (Render Web Service, docs/DEPLOY.md).
# Terpisah dari docker/8.3 (Sail lokal). Build dari root repo:
#   docker build -f docker/production/Dockerfile -t eduwgo .
# Apache + mod_php, satu proses, listen di $PORT (Render mengisi PORT otomatis).

# ---------- base: PHP 8.3 + Apache + ekstensi ----------
FROM php:8.3-apache-bookworm AS base

ENV TZ=Asia/Jakarta \
    PORT=8080 \
    APACHE_DOCUMENT_ROOT=/var/www/html/public

# Ekstensi: pdo_mysql zip exif pcntl bcmath gd opcache (mbstring sudah bawaan image php resmi)
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql zip exif pcntl bcmath gd opcache \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers \
    && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/production/php.ini "$PHP_INI_DIR/conf.d/zz-eduwgo.ini"
COPY docker/production/apache.conf /etc/apache2/sites-available/000-default.conf
# Listen di port dari env PORT (dibaca Apache saat start)
RUN echo 'Listen ${PORT}' > /etc/apache2/ports.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ---------- vendor: dependensi Composer tanpa dev ----------
FROM base AS vendor

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

# ---------- assets: build Vite ----------
FROM node:22-bookworm-slim AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
# Tailwind memindai view pagination di vendor/
COPY --from=vendor /var/www/html/vendor ./vendor
RUN npm run build

# ---------- final ----------
FROM base AS production

COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /var/www/html/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && php artisan package:discover --ansi \
    && php artisan storage:link \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && rm /usr/bin/composer

COPY docker/production/entrypoint.sh /usr/local/bin/eduwgo-entrypoint
RUN chmod +x /usr/local/bin/eduwgo-entrypoint

EXPOSE 8080

ENTRYPOINT ["eduwgo-entrypoint"]
CMD ["apache2-foreground"]
