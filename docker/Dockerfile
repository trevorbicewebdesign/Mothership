FROM joomla:5.0-php8.2-apache

# Copy your Mothership extension into Joomla tmp
COPY . /var/www/html/tmp/mothership

# Fix ownership
RUN chown -R www-data:www-data /var/www/html/tmp/mothership

# Create the CLI folder, add Joomla CLI script manually
RUN mkdir -p /var/www/html/cli && \
    curl -sSL https://raw.githubusercontent.com/joomla/joomla-cms/5.0-dev/cli/joomla.php -o /var/www/html/cli/joomla.php && \
    chmod +x /var/www/html/cli/joomla.php && \
    chown -R www-data:www-data /var/www/html
