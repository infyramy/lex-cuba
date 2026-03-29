# CORRAD Laravel CMS

A full-stack CMS for legal education content — admin panel + REST API for mobile apps.

**Stack**: Laravel 13 · PHP 8.3 · PostgreSQL · Vue 3 · TypeScript · Tailwind CSS 4 · Sanctum

---

## What's Inside

| Service | Purpose | Port |
|---------|---------|------|
| Admin Panel | Vue 3 SPA for content management | 8081 |
| API | Laravel REST API (SPA + mobile) | 8080 |
| Database | PostgreSQL 16 | internal |
| Queue | Background job worker | internal |
| Scheduler | Cron replacement | internal |

---

## Quick Start (Docker)

```bash
git clone <your-repo-url> corrad && cd corrad
cp .env.production.example .env
# Edit .env — set DB_PASSWORD, APP_URL, VITE_API_BASE_URL, and domain settings
docker compose up -d --build
```

Wait ~30 seconds for auto-migration and seeding. Then open `http://localhost:8081`.

**Default login:**
- Email: `admin@example.com`
- Password: `admin12345`

Change the password immediately after first login.

---

## Mobile API

The API is ready for mobile app integration:

```
Base URL:  https://api.your-domain.com
Register:  POST /api/mobile/register
Login:     POST /api/mobile/login
Profile:   GET  /api/mobile/me          (Bearer token)
Content:   GET  /api/mobile/notes, /categories, /questions, etc.
```

Use `Authorization: Bearer <token>` header for authenticated requests. Tokens expire after 30 days.

Full API reference: `https://admin.your-domain.com/admin/api-reference` (after login).

---

## Local Development

```bash
# Install dependencies
composer install
cd client && npm install && cd ..

# Set up SQLite for local dev
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

# Run everything
composer dev   # starts API + Vite dev server together
```

Tests: `composer test`

---

## Guides

| Guide | What it covers |
|-------|---------------|
| [DOCKER.md](DOCKER.md) | Dokploy, Docker Compose, bare Ubuntu deployment |
| [SECURITY.md](SECURITY.md) | Auth, RBAC, rate limiting, audit logging |
| [CLAUDE.md](CLAUDE.md) | AI coding conventions for this project |

---

## Tech Notes

- **Auth**: Sanctum session cookies for the admin SPA, Bearer tokens for mobile
- **RBAC**: Role-based permissions via `app/Enums/Permission.php` + `CheckPermission` middleware
- **Case conversion**: Automatic camelCase ↔ snake_case via middleware — never convert manually
- **Audit logging**: All data mutations tracked via the `Auditable` trait on every model
