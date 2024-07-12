FROM php:8.2-fpm

WORKDIR /var/www

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libzip-dev \
    libgd-dev

# Install PHP extensions
RUN docker-php-ext-install zip gd bcmath

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

# Copy application source
COPY . /var/www/

# Set permissions (if needed)
RUN chown -R www-data:www-data /var/www

# Run Composer Install
RUN composer install --ignore-platform-req=php

# Change current user to www-data
USER www-data

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
