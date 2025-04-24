FROM joomla:5.0-php8.2-apache

# Copy Mothership to Joomla tmp directory
COPY . /var/www/html/tmp/mothership

# Fix ownership for Joomla installer
RUN chown -R www-data:www-data /var/www/html/tmp/mothership
