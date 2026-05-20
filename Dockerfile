# 1. Gunakan image PHP resmi
FROM php:8.3-cli

# 2. Install library sistem operasi
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git curl pkg-config libssl-dev

# 3. Install core PHP extensions (DITAMBAHKAN ZIP DI SINI)
RUN docker-php-ext-install pdo_mysql bcmath sockets zip

# 4. Install MongoDB extension
RUN pecl install mongodb && docker-php-ext-enable mongodb

# 5. Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. Set environment
WORKDIR /app
COPY . .

# 7. Install dependencies
RUN composer install --optimize-autoloader --no-dev

# 8. Start Laravel dengan fallback port
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8000}