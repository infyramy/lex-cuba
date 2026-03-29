# ============================================================================
# CORRAD Laravel CMS — Multi-stage Docker Build
# ============================================================================
# Stage 1: Build Vue 3 frontend (needs VITE_API_BASE_URL at build time)
# Stage 2: PHP-FPM for Laravel API
# Stage 3: Nginx to serve admin SPA
# ============================================================================

# ── Stage 1: Frontend Build ──────────────────────────────────────────────────
FROM node:20-alpine AS frontend-builder

# API URL must be set at build time — it's baked into the JS bundle
ARG VITE_API_BASE_URL="https://lex-api.0w0.my"
ENV VITE_API_BASE_URL=$VITE_API_BASE_URL

WORKDIR /build
COPY client/package.json client/package-lock.json ./
RUN npm ci
COPY client/ ./
RUN npm run build

# ── Stage 2: Laravel API (PHP-FPM) ──────────────────────────────────────────
FROM php:8.4-fpm-alpine AS api

RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    oniguruma-dev \
    && docker-php-ext-install \
    pdo_pgsql \
    bcmath \
    mbstring \
    zip \
    opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Install PHP dependencies first (Docker cache layer)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy application code
COPY . .
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

# ── Stage 3: Admin SPA (Nginx) ──────────────────────────────────────────────
FROM nginx:1.27-alpine AS admin
COPY --from=frontend-builder /build/dist /usr/share/nginx/html
COPY docker/nginx/admin.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
