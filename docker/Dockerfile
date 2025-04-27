FROM joomla:5.0-php8.2-apache

# Copy your Mothership component into Joomla tmp
COPY . /var/www/html/tmp/mothership

# Fix ownership for Joomla installer
RUN chown -R www-data:www-data /var/www/html/tmp/mothership

# Add Joomla CLI manually
RUN curl -sSL https://raw.githubusercontent.com/joomla/joomla-cms/5.0-dev/cli/joomla.php -o /var/www/html/cli/joomla.php && \
    chmod +x /var/www/html/cli/joomla.php && \
    chown -R www-data:www-data /var/www/html


