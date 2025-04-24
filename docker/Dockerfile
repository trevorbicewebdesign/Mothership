FROM joomla:5.0-php8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    unzip git curl zip php-mysql php-curl php-xml php-mbstring php-zip php-intl php-bcmath

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Codeception globally
RUN composer global require codeception/codeception

# Copy this directory (minus what's ignored) into Joomla's tmp
COPY . /var/www/html/tmp/mothership

# Set ownership
RUN chown -R www-data:www-data /var/www/html/tmp/mothership
