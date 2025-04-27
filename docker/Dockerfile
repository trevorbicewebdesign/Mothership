FROM joomla:5.0-php8.2-apache

# Copy your Mothership extension into Joomla tmp
COPY . /var/www/html/tmp/mothership

# Fix ownership
RUN chown -R www-data:www-data /var/www/html/tmp/mothership

# Install Composer globally (if not already installed)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Codeception
RUN composer global require codeception/codeception