FROM php:8.2-fpm-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    oniguruma-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    bash

RUN docker-php-ext-install pdo_pgsql pdo_mysql mbstring zip gd opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

# Run Composer Autoloader optimization
RUN composer install --no-interaction --optimize-autoloader --no-dev || true

EXPOSE 9000

CMD ["php-fpm"]
