FROM php:8.2-apache

# Fix Apache MPM conflict
RUN a2dismod mpm_event && a2enmod mpm_prefork

# Install MySQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy project files
COPY . /var/www/html/

# Enable rewrite
RUN a2enmod rewrite

EXPOSE 80