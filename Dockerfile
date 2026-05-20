# Pakai PHP 8.3 sesuai laptopmu
FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git curl pkg-config libssl-dev

RUN docker-php-ext-install pdo_mysql bcmath sockets zip

# Install ekstensi MongoDB
RUN pecl install mongodb && docker-php-ext-enable mongodb

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --optimize-autoloader --no-dev

# Perintah khusus untuk Render
CMD php artisan serve --host=0.0.0.0 --port=$PORT