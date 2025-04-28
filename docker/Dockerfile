# Start from plain PHP-Apache
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    git \
    curl \
    mariadb-client \
    && docker-php-ext-install mysqli zip gd

# Download and unzip Joomla
WORKDIR /var/www/html
RUN curl -sSL https://downloads.joomla.org/cms/joomla5/5-3-0/Joomla_5-3-0-Stable-Full_Package.zip?format=zip -o joomla.zip \
    && unzip joomla.zip \
    && rm joomla.zip

# Fix permissions
RUN chown -R www-data:www-data /var/www/html

# Install Composer (global)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN composer install --dev --prefer-dist

# Expose Apache port
EXPOSE 80
