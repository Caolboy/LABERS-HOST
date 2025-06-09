# Base image with PHP 8.2 + Apache
FROM php:8.2-apache

# Install system dependencies and PHP extensions required by Laravel
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libcurl4-openssl-dev \
    zip \
    nodejs \
    npm \
    && docker-php-ext-install pdo_mysql zip mbstring xml intl curl \
    && a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy composer binary from official composer image for faster install
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . /var/www/html

# Copy SSL CA certificate into container (adjust path if needed)
COPY ca.pem /etc/ssl/certs/ca.pem

# Install PHP dependencies without dev and optimize autoloader (scripts will run)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Install Node.js dependencies and build assets
RUN npm install && npm run build

# Fix permissions for Laravel storage, cache, and .env file
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/.env

# Change Apache DocumentRoot to /var/www/html/public
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf \
 && sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s|AllowOverride None|AllowOverride All|' /etc/apache2/apache2.conf

# Expose port 80

EXPOSE 80

# Clear config and cache at container startup then start Apache
CMD php artisan config:clear && \
    php artisan cache:clear && \
    php artisan config:cache && \
    apache2-foreground
