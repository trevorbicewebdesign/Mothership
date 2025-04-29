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
    && docker-php-ext-install mysqli pdo pdo_mysql zip gd

# Install Composer (global)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Download and unzip Joomla
WORKDIR /var/www/html
RUN curl -sSL https://downloads.joomla.org/cms/joomla5/5-3-0/Joomla_5-3-0-Stable-Full_Package.zip?format=zip -o joomla.zip \
    && unzip joomla.zip \
    && rm joomla.zip

# Copy *your* composer.json and composer.lock INTO Joomla root
# Ensure composer.json and composer.lock exist in the build context
COPY /docker/composer.json ./composer.json
COPY /docker/composer.lock ./composer.lock

# Install the dev dependencies (e.g., Codeception, Helper\DbHelper)
RUN composer install --no-interaction --no-scripts --prefer-dist

# Fix permissions AFTER
RUN chown -R www-data:www-data /var/www/html

RUN rm -r /var/www/html/installation

# Expose Apache port
EXPOSE 80
