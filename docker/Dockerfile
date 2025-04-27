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
ARG JOOMLA_VERSION=5.0.3   # <--- CONTROL the Joomla version here
WORKDIR /var/www/html
RUN curl -sSL https://downloads.joomla.org/cms/joomla${JOOMLA_VERSION}/Joomla_${JOOMLA_VERSION}-Stable-Full_Package.zip?format=zip -o joomla.zip \
    && unzip joomla.zip \
    && rm joomla.zip

# Fix permissions
RUN chown -R www-data:www-data /var/www/html

# Copy your Mothership extension (optional here — usually installed later in tests)
COPY ./tmp/mothership /var/www/html/tmp/mothership
RUN chown -R www-data:www-data /var/www/html/tmp/mothership

# Install Composer (global)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Codeception (global)
RUN composer global require codeception/codeception

# Expose Apache port
EXPOSE 80
