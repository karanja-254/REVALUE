FROM php:8.2-cli

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libjpeg-dev libonig-dev libxml2-dev libsqlite3-dev sqlite3 \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd pdo_sqlite zip bcmath exif pcntl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
EXPOSE 8000
