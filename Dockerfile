FROM php:8.3-apache

# ------------------------------------------------------------
# System dependencies
# ------------------------------------------------------------

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*


# ------------------------------------------------------------
# PHP extensions
# ------------------------------------------------------------

RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache


# ------------------------------------------------------------
# Apache configuration
# ------------------------------------------------------------

RUN a2enmod rewrite headers expires


# Laravel public directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri \
    -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf


# ------------------------------------------------------------
# Composer
# ------------------------------------------------------------

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


# ------------------------------------------------------------
# Application
# ------------------------------------------------------------

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

COPY . .

RUN composer dump-autoload --optimize \
    && php artisan package:discover --ansi \
    && php artisan filament:upgrade


COPY . .


# ------------------------------------------------------------
# Laravel permissions
# ------------------------------------------------------------

RUN chown -R www-data:www-data \
    storage \
    bootstrap/cache


# ------------------------------------------------------------
# Laravel production optimisations
# ------------------------------------------------------------

RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache


EXPOSE 80

CMD ["apache2-foreground"]
