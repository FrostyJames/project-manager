# Stage 1: Build PHP/Laravel backend first
FROM php:8.2-fpm AS backend

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --no-dev --optimize-autoloader

# Stage 2: Build frontend assets
FROM node:22 AS frontend

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm install -g npm@latest && npm install

# Copy resources and configs
COPY resources ./resources
COPY vite.config.js ./
COPY --from=backend /var/www/vendor ./vendor   

RUN npm run build

# Stage 3: Final image
FROM php:8.2-fpm

WORKDIR /var/www

COPY . .
COPY --from=backend /var/www/vendor ./vendor
COPY --from=frontend /app/dist ./public/build

RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
