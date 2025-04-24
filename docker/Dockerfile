FROM joomla:5.0-php8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y lsb-release gnupg2 ca-certificates curl && \
    echo "deb https://packages.sury.org/php $(lsb_release -sc) main" > /etc/apt/sources.list.d/sury-php.list && \
    curl -fsSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /usr/share/keyrings/php-archive-keyring.gpg && \
    apt-get update && apt-get install -y \
    unzip git zip \
    php8.2 php8.2-cli php8.2-common php8.2-mysql php8.2-curl php8.2-xml php8.2-mbstring php8.2-zip php8.2-intl php8.2-bcmath && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Codeception globally
RUN composer global require codeception/codeception

# Copy this directory (minus what's ignored) into Joomla's tmp
COPY . /var/www/html/tmp/mothership

# Set ownership
RUN chown -R www-data:www-data /var/www/html/tmp/mothership
