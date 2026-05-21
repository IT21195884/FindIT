# Use official PHP 8.2 with Apache on Linux
FROM php:8.2-apache

# Install PHP extensions needed for MySQL PDO
RUN docker-php-ext-install pdo pdo_mysql

# Install ca-certificates for PlanetScale SSL
RUN apt-get update && apt-get install -y ca-certificates unzip && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory to Apache web root
WORKDIR /var/www/html

# Copy all project files into the container
COPY . .

# Install PHP dependencies (PHPMailer)
RUN composer install --no-dev --optimize-autoloader

# Create uploads directory and set correct permissions
RUN mkdir -p /var/www/html/uploads && \
    chown -R www-data:www-data /var/www/html/uploads && \
    chmod -R 755 /var/www/html/uploads

# Set correct permissions for the whole project
RUN chown -R www-data:www-data /var/www/html

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Expose port 80
EXPOSE 80