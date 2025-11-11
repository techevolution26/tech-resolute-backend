# Dockerfile — Laravel-ready, PHP 8.3, exif + gd + zip installed
# Base image for runtime
FROM php:8.3-fpm-bookworm AS base

# System packages and build deps
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
    ca-certificates curl gnupg2 git unzip zip libzip-dev libonig-dev libxml2-dev \
    libpng-dev libjpeg-dev libfreetype6-dev build-essential pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions we need
RUN docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j"$(nproc)" pdo pdo_mysql mbstring exif xml zip gd \
    && pecl channel-update pecl.php.net || true

# Composer in image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first to leverage layer cache
COPY composer.json composer.lock ./

# Install PHP deps (no dev) before app code
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts || true

# Build stage for assets (node)
FROM base as nodebuilder

# Install node (use official Node binary)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get update && apt-get install -y nodejs npm \
    && npm -v || true

WORKDIR /var/www/html
COPY package.json package-lock.json* ./
RUN npm ci --legacy-peer-deps || npm install --no-audit --no-fund
RUN npm run build || true

# Final image: copy app code and artifacts
FROM base

WORKDIR /var/www/html

# Copy PHP vendor artifacts (we installed earlier in base)
COPY --from=base /var/www/html/vendor ./vendor

# Copy node build artifacts
COPY --from=nodebuilder /var/www/html/node_modules ./node_modules
COPY --from=nodebuilder /var/www/html/public ./public

# Copy application
COPY . .

# Ensure storage directories exist and writable
RUN mkdir -p storage/framework/{sessions,views,cache,testing} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache public \
    && chmod -R ug+rw storage bootstrap/cache

# Expose port used by php-fpm (if Railway expects http via Caddy / Caddyfile, adapt accordingly)
EXPOSE 9000

# Default command — use php-fpm; Railway can run its start script if you have one
CMD ["php-fpm"]
