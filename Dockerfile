FROM php:8.2-cli-alpine

RUN apk add --no-cache \
    sqlite \
    sqlite-dev \
    composer \
    git \
    curl \
    oniguruma-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    pdo \
    pdo_sqlite \
    mbstring \
    exif \
    pcntl \
    session \
    fileinfo \
    tokenizer \
    dom \
    xml \
    ctype \
    filter \
    hash \
    json \
    bcmath

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-interaction --prefer-dist

COPY . .

RUN chmod -R 775 storage bootstrap/cache

EXPOSE 8000
