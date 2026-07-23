# --- Stage 1: compile Tailwind/Vite assets ---
# Needed because layouts/app.blade.php pulls its CSS/JS via @vite() now
# (the previous version loaded Tailwind straight from a CDN script tag,
# which never needed a build step — production apps shouldn't rely on
# cdn.tailwindcss.com, so this stage replaces it with a real, cached,
# versioned build).
FROM node:20-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js ./
RUN npm run build

# --- Stage 2: the PHP application ---
FROM php:8.2-fpm

# nginx + supervisor run alongside php-fpm in this single container.
# libpng/onig/xml/zip are the standard Laravel extension build deps.
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Install dependencies first so this layer is cached across code-only changes.
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

COPY . .
# Pull in the compiled assets + manifest built in stage 1, since this final
# image never runs npm itself.
COPY --from=assets /app/public/build ./public/build
RUN composer dump-autoload --optimize --no-dev

# Config: nginx site, php-fpm pool, supervisor, and the startup script.
COPY docker/nginx.conf /etc/nginx/sites-enabled/default
COPY docker/www.conf /usr/local/etc/php-fpm.d/zz-www.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 10000

CMD ["/usr/local/bin/entrypoint.sh"]
