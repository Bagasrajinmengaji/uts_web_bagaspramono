# Gunakan image resmi PHP dengan Apache
FROM php:8.2-apache

# Instal dependensi sistem dan ekstensi PHP yang diperlukan (GD, Zip, PDO MySQL)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql zip \
    && a2enmod rewrite

# Ubah konfigurasi Apache untuk port (jika Render memberikan port kustom)
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Set working directory ke default Apache document root
WORKDIR /var/www/html

# Salin Composer dari image resmi Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Salin seluruh berkas proyek ke dalam container
COPY . /var/www/html/

# Jalankan instalasi composer (dependencies)
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader; fi

# Ubah hak milik file agar Apache bisa mengaksesnya
RUN chown -R www-data:www-data /var/www/html

# Port default yang dibuka
EXPOSE 80
