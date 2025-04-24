FROM joomla:5.0-php8.2-apache

# Install required packages
RUN apt-get update && apt-get install -y \
    unzip git curl zip php-mysql php-curl php-xml php-mbstring php-zip php-intl php-bcmath

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Codeception globally
RUN composer global require codeception/codeception

# Copy custom PHP config (optional)
COPY docker/php.ini /usr/local/etc/php/php.ini

# Copy optional Joomla CLI if missing
COPY cli/joomla.php /var/www/html/cli/joomla.php

# Copy Mothership component to tmp for CLI install
COPY mothership /var/www/html/tmp/mothership

# Set permissions
RUN chown -R www-data:www-data /var/www/html
