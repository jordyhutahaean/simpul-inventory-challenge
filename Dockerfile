FROM php:8.4-cli

# Official PHP images already include pdo_sqlite, mbstring, tokenizer, xml, etc.
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install dependencies first so Docker can cache this layer
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY . .
RUN composer dump-autoload --optimize \
    && php artisan package:discover --ansi || true

# Windows checkouts can leave CRLF line endings, which break the shebang
RUN sed -i 's/\r$//' docker/start.sh && chmod +x docker/start.sh

# Let the built-in server handle several requests at once
ENV PHP_CLI_SERVER_WORKERS=4

EXPOSE 10000
CMD ["bash", "docker/start.sh"]
