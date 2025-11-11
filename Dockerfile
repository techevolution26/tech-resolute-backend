# Dockerfile — fixed: using official node image for the node build stage

FROM php:8.3-fpm-bookworm AS base

# System packages and build deps minimal; for compiling extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
    ca-certificates curl git unzip zip libzip-dev libonig-dev libxml2-dev \
    libpng-dev libjpeg-dev libfreetype6-dev build-essential pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j"$(nproc)" pdo pdo_mysql mbstring exif xml zip gd \
    && pecl channel-update pecl.php.net || true

# Install Composer (official)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first to leverage layer cache
COPY composer.json composer.lock ./

# Install PHP deps (no dev in production)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Node build stage
FROM node:22-bullseye-slim AS nodebuilder

WORKDIR /var/www/html

# Copying package files and install
COPY package*.json ./
RUN npm ci --legacy-peer-deps

# Copy app (so build scripts can import resources) and build assets
# If your build needs other files, copy them as well (e.g., webpack.mix.js, tailwind config, resources)
COPY . .
RUN npm run build || true

# Final image
FROM base AS final

WORKDIR /var/www/html

# Copy vendor from base stage (composer already ran in base)
COPY --from=base /var/www/html/vendor ./vendor

# Copy node build artifacts and node_modules (if you need them in runtime)
COPY --from=nodebuilder /var/www/html/public ./public
COPY --from=nodebuilder /var/www/html/node_modules ./node_modules

# Copy the rest of the application
COPY . .

# Ensure storage directories exist and are writable
RUN mkdir -p storage/framework/{sessions,views,cache,testing} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache public \
    && chmod -R ug+rw storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
