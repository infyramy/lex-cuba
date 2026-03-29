# CORRAD Laravel CMS — Deployment Guide

Three options: Docker Compose (recommended), Dokploy, or bare Ubuntu.

For Docker/Dokploy setup see **[DOCKER.md](DOCKER.md)** — it covers all three in detail.

---

## Quick Reference: Docker Compose

```bash
git clone <your-repo-url> corrad && cd corrad
cp .env.production.example .env
# Edit .env — set DB_PASSWORD, APP_URL, VITE_API_BASE_URL, SESSION_DOMAIN, SANCTUM_STATEFUL_DOMAINS
docker compose up -d --build
```

Services after startup:

| Service | URL | Notes |
|---------|-----|-------|
| Admin Panel | `http://localhost:8081` | Vue SPA |
| API | `http://localhost:8080/api` | Laravel |
| PostgreSQL | internal | No external port |

The API auto-migrates and seeds on first run. See [DOCKER.md](DOCKER.md) for domain setup.

**Stop:** `docker compose down`
**Logs:** `docker compose logs -f api`
**Rebuild:** `docker compose up -d --build`

---

## Bare Metal: Ubuntu Server

### 1. Install Dependencies

```bash
# PHP 8.3
sudo apt install php8.3 php8.3-fpm php8.3-pgsql php8.3-mbstring \
  php8.3-xml php8.3-bcmath php8.3-fileinfo php8.3-curl php8.3-zip

# PostgreSQL 16
sudo apt install postgresql-16

# Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install nodejs

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Nginx
sudo apt install nginx
```

### 2. PostgreSQL Setup

```bash
sudo -u postgres psql

CREATE DATABASE corrad;
CREATE USER corrad WITH ENCRYPTED PASSWORD 'your-strong-password';
GRANT ALL PRIVILEGES ON DATABASE corrad TO corrad;
\c corrad
GRANT ALL ON SCHEMA public TO corrad;
\q
```

### 3. Deploy Application

```bash
cd /var/www
git clone <your-repo-url> corrad && cd corrad

# Install dependencies
composer install --no-dev --optimize-autoloader

# Configure environment
cp .env.production.example .env
nano .env   # Set DB_HOST=127.0.0.1, DB_PASSWORD, APP_URL, domains

# Initialize
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force   # First time only
php artisan storage:link

# Build frontend
cd client && npm install && npm run build && cd ..

# Cache for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 4. Nginx Config

Create `/etc/nginx/sites-available/corrad-api`:

```nginx
server {
    listen 80;
    server_name api.your-domain.com;
    root /var/www/corrad/public;
    client_max_body_size 25M;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location /storage { try_files $uri $uri/ =404; }
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    location ~ /\. { deny all; }
}
```

Create `/etc/nginx/sites-available/corrad-admin`:

```nginx
server {
    listen 80;
    server_name admin.your-domain.com;
    root /var/www/corrad/client/dist;
    location / { try_files $uri $uri/ /index.html; }
    location /assets/ { expires 1y; add_header Cache-Control "public, immutable"; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/corrad-api /etc/nginx/sites-enabled/
sudo ln -s /etc/nginx/sites-available/corrad-admin /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# SSL
sudo certbot --nginx -d api.your-domain.com -d admin.your-domain.com
```

### 5. Queue Worker (systemd)

Create `/etc/systemd/system/corrad-worker.service`:

```ini
[Unit]
Description=CORRAD Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
WorkingDirectory=/var/www/corrad
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable corrad-worker
sudo systemctl start corrad-worker
```

### 6. Scheduler (cron)

```bash
sudo crontab -u www-data -e
# Add:
* * * * * cd /var/www/corrad && php artisan schedule:run >> /dev/null 2>&1
```

---

## After Each Deploy

```bash
cd /var/www/corrad
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
cd client && npm install && npm run build && cd ..
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo systemctl restart corrad-worker
```

---

## Database Backup

```bash
# Manual
pg_dump -U corrad -h 127.0.0.1 corrad > backup_$(date +%Y%m%d).sql

# Automated (add to cron)
0 2 * * * pg_dump -U corrad -h 127.0.0.1 corrad | gzip > /var/backups/corrad/backup_$(date +\%Y\%m\%d).sql.gz
```

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| 500 error | `tail storage/logs/laravel.log` |
| 502 bad gateway | `sudo systemctl status php8.3-fpm` |
| Permission denied | Re-run `chown`/`chmod` from step 3 |
| DB connection refused | `sudo systemctl status postgresql` |
| CSRF mismatch | Check `SANCTUM_STATEFUL_DOMAINS` matches your domain |
| Mobile auth fails | Use `Authorization: Bearer <token>` header |
| Uploads not showing | `php artisan storage:link` |
