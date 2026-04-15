FROM php:8.3-cli

WORKDIR /var/www

RUN apt-get update && apt-get install -y --no-install-recommends \
    curl unzip git zip libpng-dev libssl-dev pkg-config \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions mbstring sqlite3 pcntl gd http zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Match the typical host uid/gid so bind-mounted files have correct ownership
RUN groupadd -g 1000 www && useradd -u 1000 -ms /bin/bash -g www www

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

USER www
EXPOSE 8000
ENTRYPOINT ["/entrypoint.sh"]
