# CORRAD Laravel CMS — Security Reference

## Security Overview

CORRAD implements defense-in-depth security across authentication, authorization, input validation, file uploads, session management, CSRF protection, and audit logging. All API endpoints follow a standardized security pattern enforced by middleware and form request classes.

---

## Authentication

### SPA Auth (Admin Panel)

- **Method**: Laravel Sanctum session-based authentication
- **CSRF**: XSRF-TOKEN cookie automatically managed by Sanctum
- **Credentials**: All requests include `credentials: "include"` for cookie transmission
- **Flow**: `GET /sanctum/csrf-cookie` -> `POST /api/auth/login` with session cookie

### Mobile Auth (API Tokens)

- **Method**: Sanctum token-based authentication (Bearer token)
- **Token Expiry**: 30 days (`expiration` config in `sanctum.php`)
- **Header**: `Authorization: Bearer {token}`
- **Endpoints**: `POST /api/mobile/register`, `POST /api/mobile/login`

### Password Hashing

- **Algorithm**: bcrypt
- **Rounds**: 12 (production)
- **Implementation**: Laravel `hashed` cast on User model — passwords are never stored or transmitted in plain text

---

## Authorization (RBAC)

### Permission System

- **Registry**: `app/Enums/Permission.php` — plain class with string constants (not a PHP enum)
- **Pattern**: `const {MODULE}_{ACTION} = '{module}.{action}'`
- **Middleware**: `CheckPermission` registered as alias `permission`
- **Usage**: `->middleware('permission:notes.view')`

### Roles

| Role | Permissions |
|------|-------------|
| Admin | `Permission::all()` — full access to every module |
| Content Manager | `Permission::contentManagerPermissions()` — content and member viewing only, no system access |

### Permission Modules

| Module | Actions |
|--------|---------|
| notes | view, create, edit, delete |
| case_summaries | view, create, edit, delete |
| questions | view, create, edit, delete |
| categories | view, create, edit, delete |
| topic_links | view, create, edit, delete |
| free_links | view, create, edit, delete |
| statutes | view, create, edit, delete |
| media | view, upload, delete |
| users | view, create, edit, delete |
| members | view, create, edit, delete |
| packages | view, create, edit, delete |
| roles | view, create, edit, delete |
| settings | view, edit |
| audit | read |

Every new module must add constants to `Permission.php` AND register them in `Permission::all()`.

---

## Rate Limiting

Configured in `bootstrap/app.php`:

| Limiter | Limit | Scope |
|---------|-------|-------|
| `login` | 20 requests / 10 minutes | Per IP |
| `api` | 120 requests / minute | Per authenticated user (falls back to IP) |
| `uploads` | 30 requests / minute | Per authenticated user (falls back to IP) |

Rate limit exceeded returns HTTP 429 with error code `TOO_MANY_REQUESTS`.

---

## Input Validation

- All mutating API endpoints use Form Request classes extending `BaseFormRequest`
- Inline `$request->validate()` is a **forbidden pattern** — always use Form Request classes
- All foreign keys validated with `exists:table,column`
- `BaseFormRequest` ensures JSON error responses matching the API envelope format
- Password fields require `min:8` character minimum

---

## File Upload Security

| Resource | Allowed MIME Types | Max Size |
|----------|-------------------|----------|
| Notes | PDF | 10 MB |
| Question Papers | PDF | 20 MB |
| Statutes | PDF | 20 MB |
| Media (images) | jpeg, png, gif, webp | 5 MB |

### Safeguards

- MIME type validation enforced via `mimes:` rule in Form Requests
- Max file size enforced via `max:` rule
- Filename sanitization: lowercase, alphanumeric + dashes only
- Storage via `Storage::disk('public')` with safe, non-executable paths
- `Content-Type` header never set manually for `FormData` uploads

---

## Session Security

| Setting | Value | Environment |
|---------|-------|-------------|
| `SESSION_ENCRYPT` | `true` | Production |
| `SESSION_SECURE_COOKIE` | `true` | Production |
| `HttpOnly` | `true` | All |
| `SameSite` | `Lax` | All |

---

## CSRF Protection

- Sanctum manages CSRF via the `XSRF-TOKEN` cookie
- The frontend reads the cookie automatically and includes it in requests
- CSRF protection must never be disabled
- `CamelCaseMiddleware` is applied globally to the `api` middleware group for automatic key conversion

---

## Audit Logging

- All data models use the `Auditable` trait (`App\Http\Traits\Auditable`)
- Captures: user ID, action, entity type, entity ID, old values, new values, IP address, user agent
- Auth events (login/logout) logged via `AuditService::logAuth()`
- Sensitive fields (`password`, `remember_token`) are automatically filtered from audit logs
- Audit logs can be pruned via `PruneAuditLogs` job

---

## Checklist: Adding a New Module Securely

1. **Migration**: Use `$table->id()`, `$table->timestamps()`, foreign keys with `->constrained()->nullOnDelete()`
2. **Model**: Declare `$fillable` explicitly (never `$guarded = []`), add `Auditable` trait, hide sensitive fields
3. **Form Requests**: Extend `BaseFormRequest`, validate all foreign keys with `exists:`, validate file MIME types and sizes
4. **Controller**: Use `ApiResponse` trait, never return raw `response()->json()`
5. **Permissions**: Add constants to `Permission.php`, register in `Permission::all()`
6. **Routes**: Add inside `auth:sanctum` group, apply `permission:module.action` middleware
7. **Tests**: Cover success, validation error (422), auth guard (401), and RBAC (403) cases

---

## Common Attack Vectors — Protections

| Attack | Protection |
|--------|-----------|
| SQL Injection | Eloquent ORM query builder — no raw SQL in controllers |
| Cross-Site Scripting (XSS) | Vue 3 auto-escaping of template expressions |
| Cross-Site Request Forgery (CSRF) | Sanctum XSRF-TOKEN cookie |
| Mass Assignment | Explicit `$fillable` arrays on all models (`$guarded = []` is forbidden) |
| Brute Force (login) | Rate limiting: 20 attempts / 10 minutes per IP |
| Brute Force (API) | Rate limiting: 120 requests / minute per user |
| Unauthorized Access | Sanctum `auth:sanctum` middleware on all protected routes |
| Privilege Escalation | `CheckPermission` middleware with granular RBAC |
| Sensitive Data Exposure | `$hidden` on models, audit log filtering, `hashed` cast for passwords |
| File Upload Exploits | MIME validation, size limits, filename sanitization |
