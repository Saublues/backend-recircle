# 1. Gunakan image PHP resmi yang terbukti stabil
FROM php:8.2-cli

# 2. Install library sistem operasi yang dibutuhkan
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git libssl-dev pkg-config curl

# 3. Install ekstensi PHP untuk Laravel dan Reverb
RUN docker-php-ext-install pdo_mysql bcmath sockets

# 4. Install ekstensi MongoDB (Dijamin sukses di sini)
RUN pecl install mongodb && docker-php-ext-enable mongodb

# 5. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. Masukkan semua file project kamu ke dalam sistem
WORKDIR /app
COPY . .

# 7. Install package Laravel
RUN composer install --optimize-autoloader --no-dev

# 8. Jalankan server Laravel (Otomatis mendengarkan Port dari Railway)
CMD php artisan serve --host=0.0.0.0 --port=$PORT