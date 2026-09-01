# Stage 1: Build frontend assets
FROM node:22 AS frontend

WORKDIR /app

# Copy only package files first for caching
COPY package.json package-lock.json* ./
RUN npm install -g npm@latest && npm install

# Copy the rest of the frontend code
COPY resources ./resources
COPY vite.config.js ./
COPY tailwind.config.js ./
COPY postcss.config.js ./

# Build frontend assets
RUN npm run build


# Stage 2: Build PHP/Laravel backend
FROM php:8.2-fpm AS backend

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy Laravel files
COPY . .

# Install PHP dependencies (production only)
RUN composer install --no-dev --optimize-autoloader

# Copy built frontend assets into public folder
COPY --from=frontend /app/dist ./public/build

# Optimize Laravel
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Expose port
EXPOSE 8000

# Start Laravel server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
