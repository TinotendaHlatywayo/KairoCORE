# =============================================================================
# Laravel Application Container (PHP-FPM)
# Base image: php:8.3-fpm (matches the "php": "^8.3" requirement in composer.json)
# =============================================================================
FROM php:8.3-fpm

# -----------------------------------------------------------------------------
# System dependencies, including the -dev libraries required to build the
# PHP extensions below (gd, zip, mbstring, intl).
# -----------------------------------------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        curl \
        unzip \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
        libzip-dev \
        libicu-dev \
        default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# -----------------------------------------------------------------------------
# Composer: pulled directly from the official Composer image (no curl installer)
# -----------------------------------------------------------------------------
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# -----------------------------------------------------------------------------
# Application code
# -----------------------------------------------------------------------------
WORKDIR /var/www/html

COPY . /var/www/html

# -----------------------------------------------------------------------------
# Permissions: give the web server (www-data) write access to the Laravel
# runtime directories (logs, cache, sessions, uploaded files, compiled views).
# -----------------------------------------------------------------------------
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]