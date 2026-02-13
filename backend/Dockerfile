# Backend Laravel POS - PHP 8.2
FROM php:8.2-cli-bookworm AS base

# Install system deps + PHP extensions for Laravel
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libxml2-dev \
    libonig-dev \
    libicu-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpq-dev \
    default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pdo \
        pdo_mysql \
        pcntl \
        zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

# Composer dependencies (cache layer)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy app (excluding ignored paths via .dockerignore)
COPY . .

RUN composer dump-autoload --optimize

# Laravel: storage + cache writable
RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# .env: copy example if .env missing (will be overridden by env in production)
RUN test -f .env || (cp .env.example .env && php artisan key:generate --no-interaction || true)

# GCP Cloud Run memakai env PORT (default 8080). Lokal tetap bisa pakai port 8000.
ENV PORT=8080
EXPOSE 8080

# Entrypoint: baca PORT dari env (wajib untuk Cloud Run), lalu jalankan Laravel.
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]
