# Use the official PHP 8.2 Apache image (adjust version if needed)
FROM php:8.2-apache

# Install required system packages and PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_mysql zip

# Enable Apache mod_rewrite for your routing
RUN a2enmod rewrite

# Change the default Apache document root to your public/ directory
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set the working directory
WORKDIR /var/www/html

# Copy your application code
COPY . /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install PHP dependencies from your composer.json
RUN composer install --no-dev --optimize-autoloader

# Set permissions so the API can write logs and rate limit JSONs to the storage/ directory
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage

# Expose port 80 for Render
EXPOSE 80
