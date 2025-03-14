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
    curl \
    libssl-dev \
    pkg-config

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions mbstring pdo_mysql zip exif pcntl gd sqlite3 http

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Check if group and user exist, if not, create them
RUN if ! getent group www > /dev/null; then groupadd -g 1000 www; fi && \
    if ! getent passwd www > /dev/null; then useradd -u 1000 -ms /bin/bash -g www www; fi

# Copy existing application directory contents
COPY . /var/www

# Ensure proper ownership and permissions
RUN chown -R www:www /var/www

# Run composer install as www user
RUN su www -c "composer install --no-dev --optimize-autoloader --no-interaction"

# Ensure .env exists and run artisan key:generate only if .env.example was copied
RUN ENV_CREATED=0 && \
    if [ ! -f /var/www/.env ]; then \
        cp /var/www/.env.example /var/www/.env; \
        ENV_CREATED=1; \
    fi && \
    if [ "$ENV_CREATED" -eq 1 ]; then \
        php /var/www/artisan key:generate; \
    fi

# Run database migrations
RUN su www -c "php /var/www/artisan migrate --force"

# Change current user to www
USER www

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
