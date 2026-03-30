# ============================================================================
# CORRAD Laravel CMS — Multi-stage Docker Build
# ============================================================================

# ── Stage 1: Frontend Build ──────────────────────────────────────────────────
FROM node:20-alpine AS frontend-builder

ARG VITE_API_BASE_URL="https://lex-api.0w0.my"
ENV VITE_API_BASE_URL=$VITE_API_BASE_URL

WORKDIR /build
COPY client/package.json client/package-lock.json ./
RUN npm ci
COPY client/ ./
RUN npm run build

# ── Stage 2: API (nginx + php-fpm in one container via supervisord) ──────────
FROM php:8.4-fpm-alpine AS api

RUN apk add --no-cache \
    nginx \
    supervisor \
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

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

COPY . .
RUN composer dump-autoload --optimize

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Copy nginx config
COPY docker/nginx/cors-map.conf /etc/nginx/http.d/cors-map.conf
COPY docker/nginx/api.conf      /etc/nginx/http.d/default.conf

# Copy supervisord config
COPY docker/supervisord.conf /etc/supervisord.conf

# Copy entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]

# ── Stage 3: Admin SPA (Nginx) ──────────────────────────────────────────────
FROM nginx:1.27-alpine AS admin
COPY --from=frontend-builder /build/dist /usr/share/nginx/html
COPY docker/nginx/admin.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
