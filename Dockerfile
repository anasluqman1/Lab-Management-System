FROM php:8.2-apache

# Install Apache + PHP module properly
RUN apt-get update && apt-get install -y apache2

# Disable conflicting MPMs and enable prefork ONLY
RUN a2dismod mpm_event mpm_worker || true
RUN a2enmod mpm_prefork

# Install PHP MySQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable rewrite
RUN a2enmod rewrite

# Copy project files
COPY . /var/www/html/

# Fix permissions (important sometimes)
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80