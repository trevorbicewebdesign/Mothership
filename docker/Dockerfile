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

# Install Codeception (global)
RUN composer require \
    codeception/codeception:^5.1 \
    codeception/module-webdriver:* \
    codeception/module-db:* \
    codeception/module-asserts:^3.0 \
    codeception/module-phpbrowser:^3.0 \
    codeception/module-rest:^3.0 \
    vlucas/phpdotenv:^5.3 \
    league/factory-muffin:^3.0 \
    league/factory-muffin-faker:^2.0 \
    codeception/module-filesystem:^3.0 \
    trevorbicewebdesign/codeception-mailpit:*

# Expose Apache port
EXPOSE 80
