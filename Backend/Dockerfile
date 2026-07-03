FROM php:8.1-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        unzip \
        zip \
        default-mysql-client \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP dependencies first to leverage Docker cache.
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Copy application source.
COPY . .

# Expose the internal port used by the built-in PHP server.
EXPOSE 8080

# Render sets PORT at runtime; fallback to 8080 if absent.
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t public public/index.php"]
