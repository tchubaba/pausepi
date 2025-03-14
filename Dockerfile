FROM php:8.3-fpm

# Set working directory
WORKDIR /var/www

# Run as root to avoid permission issues
USER root

# Install dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions mbstring pdo_mysql zip exif pcntl gd sqlite3

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Add user for laravel application, if they don't already exist
RUN getent group www || groupadd -g 1000 www
RUN getent passwd www || useradd -u 1000 -ms /bin/bash -g www www

# Copy existing application directory contents
COPY . /var/www

# Copy existing application directory permissions
COPY --chown=www:www . /var/www

# Ensure .env exists before running composer install
RUN if [ ! -f /var/www/.env ]; then \
        cp /var/www/.env.example /var/www/.env; \
        ENV_CREATED=1; \
    fi && \

# Install PHP dependencies without dev packages
    composer install --no-dev --optimize-autoloader --no-interaction && \

# Generate app key if .env was copied
    if [ "$ENV_CREATED" = "1" ]; then \
        php /var/www/artisan key:generate; \
    fi && \

# Run database migrations
    php /var/www/artisan migrate --force

# Change current user to www
USER www

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
