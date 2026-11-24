FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy source code
COPY src/ /var/www/html/src/
COPY public/ /var/www/html/public/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80

# Apache configuration
RUN echo "DocumentRoot /var/www/html/public" > /etc/apache2/sites-available/000-default.conf
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/sites-available/000-default.conf
