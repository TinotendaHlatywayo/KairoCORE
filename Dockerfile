# syntax=docker/dockerfile:1.7
# =============================================================================
# Kairo CORE — production image (PHP 8.3 + Apache), built for the Kuber VPS.
# Behind Nginx Proxy Manager on a private Docker network; no host ports.
# =============================================================================

# ── Stage 1: build Vite/Tailwind assets ──────────────────────────────────────
# The Filament panels call Vite::asset(...) at runtime, so public/build/
# (manifest.json + hashed CSS) MUST exist in the image.
FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci --no-audit --no-fund
# Tailwind scans these directories for class names (@source in panel-tailwind.css)
COPY vite.config.js ./
COPY resources ./resources
COPY app ./app
COPY Modules ./Modules
RUN mkdir -p public && npm run build \
    && test -s /app/public/build/manifest.json


# ── Stage 2: runtime ─────────────────────────────────────────────────────────
FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip curl \
        libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libonig-dev libxml2-dev libicu-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache

# Apache: rewrite for Laravel, serve /public, cap workers to fit the memory limit
RUN a2enmod rewrite headers expires
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf
COPY docker/apache/kairo.conf /etc/apache2/conf-available/kairo.conf
RUN a2enconf kairo

# PHP: production ini (upload limits, opcache, hide version)
COPY docker/php/production.ini /usr/local/etc/php/conf.d/zz-production.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# Dependencies first (cached unless composer files change)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader

# Application code + compiled assets
COPY . .
COPY --from=assets /app/public/build ./public/build

# Never ship debug endpoints
RUN rm -f public/info.php public/debug-marker.txt

# dump-autoload also fires the post-autoload-dump scripts from composer.json
# (package:discover + filament:upgrade). A throw-away key is only used so
# artisan can boot at build time; the real APP_KEY comes from the environment.
RUN git config --global --add safe.directory /var/www/html \
    && export APP_KEY="base64:$(head -c32 /dev/zero | base64)" \
    && composer dump-autoload --optimize --no-dev

# !! DO NOT run config:cache / route:cache / view:cache in this Dockerfile. !!
# routes/web.php registers Route::domain(parse_url(config('app.url'))), so a
# build-time cache (no .env in CI => APP_URL=http://localhost) freezes the routes
# to "localhost" and every real host returns 404. It would also bake in the wrong
# DB_HOST and APP_KEY. The entrypoint builds these caches at start-up instead.

RUN mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/kairo-entrypoint
RUN chmod +x /usr/local/bin/kairo-entrypoint

EXPOSE 80
ENTRYPOINT ["kairo-entrypoint"]
CMD ["apache2-foreground"]
