FROM php:8.4-fpm-alpine

# Instalar dependências do sistema
RUN apk add --no-cache \
    $PHPIZE_DEPS \
    curl \
    git \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    icu-dev \
    libxml2-dev \
    linux-headers \
    mysql-client \
    nodejs \
    npm \
    shadow

# Instalar extensões PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache \
    sockets

# Instalar Redis via PECL
RUN pecl install redis \
    && docker-php-ext-enable redis

# Limpar cache
RUN apk del $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/* /tmp/*

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Ajustar usuário www-data para UID/GID 1000 (usuário comum do host)
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

# Copiar configurações
COPY php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY www.conf /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html

EXPOSE 9000

CMD ["php-fpm"]