FROM php:8.4-fpm-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    icu-dev \
    oniguruma-dev \
    && docker-php-ext-install pdo_pgsql mbstring bcmath gd zip intl exif

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application files (will be shadowed by volume mount in dev)
# We copy it to adhere to "same image dev to prod"
COPY ./backend /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html
USER www-data

CMD ["php-fpm"]
