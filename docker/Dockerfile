FROM joomla:5.0-php8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
  unzip git curl zip \
  php8.2-mysql php8.2-curl php8.2-xml php8.2-mbstring php8.2-zip php8.2-intl php8.2-bcmath

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Codeception globally
RUN composer global require codeception/codeception

# Copy this directory (minus what's ignored) into Joomla's tmp
COPY . /var/www/html/tmp/mothership

# Set ownership
RUN chown -R www-data:www-data /var/www/html/tmp/mothership
