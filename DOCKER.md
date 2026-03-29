# CORRAD — Docker Setup Guide

Everything runs from one repo. PostgreSQL included. No manual DB setup needed.

---

## What's Inside

| Service | What it does | Port |
|---------|-------------|------|
| **admin** | Admin panel (Vue SPA) | 8081 |
| **api-web** | API server (Laravel) | 8080 |
| **db** | PostgreSQL 16 database | internal |
| **queue** | Background job worker | internal |
| **scheduler** | Cron job runner | internal |

---

## Option 1: Dokploy

### Step 1 — Create project in Dokploy
1. Go to Dokploy dashboard → **Projects** → **New Project**
2. Add a new **Compose** service
3. Point to your Git repo URL
4. Set **Compose Path**: `docker-compose.yml`

### Step 2 — Set environment variables
In Dokploy's environment variables section, add:

```env
# Required — change these
DB_PASSWORD=your-strong-password-here
APP_URL=https://api.yourdomain.com
VITE_API_BASE_URL=https://api.yourdomain.com
FRONTEND_URL=https://admin.yourdomain.com
SANCTUM_STATEFUL_DOMAINS=admin.yourdomain.com,api.yourdomain.com
SESSION_DOMAIN=.yourdomain.com

# Keep these as-is
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=corrad
DB_USERNAME=corrad
APP_ENV=production
APP_DEBUG=false
BCRYPT_ROUNDS=12
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
CACHE_STORE=database
```

### Step 3 — Set domains in Dokploy
- Service `admin` → domain: `admin.yourdomain.com` → port `80`
- Service `api-web` → domain: `api.yourdomain.com` → port `80`

Dokploy handles SSL automatically via Traefik.

### Step 4 — Deploy
Click **Deploy**. Wait for build to complete (~2-3 min).

The API auto-runs migrations and seeds the database on first start. You'll see logs like:
```
=== CORRAD API Starting ===
Waiting for database...
Database is ready.
Running migrations...
Migrations complete.
First run detected — seeding database...
Seeding complete.
=== CORRAD API Ready ===
```

### Step 5 — Login
Open `https://admin.yourdomain.com`

Default login:
- **Email**: `admin@example.com`
- **Password**: `admin12345`

**Change the password immediately** in Admin Users → Edit your profile.

### Step 6 — Verify API works
```bash
curl https://api.yourdomain.com/api/settings
# Should return JSON: { "data": { "siteTitle": "LexSZA", ... } }
```

Mobile app connects to: `https://api.yourdomain.com/api/mobile/login`

---

## Option 2: Any Server with Docker

### Step 1 — Clone and configure
```bash
git clone <your-repo-url> corrad
cd corrad
cp .env.production.example .env
nano .env   # Edit the values marked with "CHANGE THIS"
```

### Step 2 — Start everything
```bash
docker compose up -d --build
```

### Step 3 — Generate app key (first time only)
```bash
docker compose exec api php artisan key:generate
```

### Step 4 — Check it's running
```bash
# Admin panel
curl http://localhost:8081

# API
curl http://localhost:8080/api/settings
```

### Step 5 — Point your domain
Set up Nginx/Caddy/Traefik on the host to reverse proxy:
- `admin.yourdomain.com` → `localhost:8081`
- `api.yourdomain.com` → `localhost:8080`

---

## Option 3: Bare Ubuntu (No Docker)

### Install dependencies
```bash
# PHP 8.3
sudo apt install php8.3 php8.3-fpm php8.3-pgsql php8.3-mbstring \
  php8.3-xml php8.3-bcmath php8.3-fileinfo php8.3-curl php8.3-zip

# PostgreSQL
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

### Setup database
```bash
sudo -u postgres psql
CREATE DATABASE corrad;
CREATE USER corrad WITH ENCRYPTED PASSWORD 'your-password';
GRANT ALL PRIVILEGES ON DATABASE corrad TO corrad;
\c corrad
GRANT ALL ON SCHEMA public TO corrad;
\q
```

### Deploy application
```bash
cd /var/www
git clone <your-repo-url> corrad && cd corrad
cp .env.production.example .env
nano .env   # Set DB_HOST=127.0.0.1, DB_PASSWORD, domains

composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link

cd client && npm install && npm run build && cd ..

php artisan config:cache
php artisan route:cache

sudo chown -R www-data:www-data storage bootstrap/cache
```

### Nginx config
Create `/etc/nginx/sites-available/corrad-api`:
```nginx
server {
    listen 80;
    server_name api.yourdomain.com;
    root /var/www/corrad/public;
    client_max_body_size 25M;
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

Create `/etc/nginx/sites-available/corrad-admin`:
```nginx
server {
    listen 80;
    server_name admin.yourdomain.com;
    root /var/www/corrad/client/dist;
    location / { try_files $uri $uri/ /index.html; }
    location /assets/ { expires 1y; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/corrad-api /etc/nginx/sites-enabled/
sudo ln -s /etc/nginx/sites-available/corrad-admin /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d api.yourdomain.com -d admin.yourdomain.com
```

---

## Database Management

### Backup
```bash
# Docker
docker compose exec db pg_dump -U corrad corrad > backup.sql

# Bare metal
pg_dump -U corrad -h 127.0.0.1 corrad > backup.sql
```

### Restore
```bash
# Docker
cat backup.sql | docker compose exec -T db psql -U corrad corrad

# Bare metal
psql -U corrad -h 127.0.0.1 corrad < backup.sql
```

### Reset database (destructive!)
```bash
docker compose exec api php artisan migrate:fresh --seed --force
```

---

## Mobile App Integration

The API is ready for mobile apps at:

```
Base URL:  https://api.yourdomain.com
Register:  POST /api/mobile/register
Login:     POST /api/mobile/login
Profile:   GET  /api/mobile/me  (Bearer token)
Content:   GET  /api/mobile/notes, /categories, /questions, etc.
```

Full API docs available at: `https://admin.yourdomain.com/admin/api-reference` (after login).

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| Can't login to admin | Check `SANCTUM_STATEFUL_DOMAINS` includes your admin domain |
| API returns CORS error | Check `FRONTEND_URL` matches your admin domain exactly |
| Admin shows blank page | Check `VITE_API_BASE_URL` was set before building |
| Mobile app can't connect | Use `Authorization: Bearer <token>` header |
| Uploads not showing | Run `docker compose exec api php artisan storage:link` |
| 500 error | `docker compose logs api` to see the error |
