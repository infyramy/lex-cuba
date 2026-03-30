# LexSZA Mobile API — Complete Reference

> **AI Prompt Guide**: This document is the single source of truth for building the LexSZA mobile app.
> Use only the endpoints, field names, and data shapes defined here. Do not invent fields or endpoints.

---

## Base URL

```
Production:  https://lex-api.0w0.my
```

All endpoints are prefixed with `/api/mobile/` unless stated otherwise.

---

## Authentication

Mobile app uses **Bearer token** authentication.

After login or register, the server returns a `token`. Include it in every protected request:

```
Authorization: Bearer <token>
Content-Type: application/json
Accept: application/json
```

Public endpoints (no token required):
- `POST /api/mobile/register`
- `POST /api/mobile/login`
- `GET  /api/settings`
- `GET  /api/mobile/categories`
- `GET  /api/mobile/packages`

---

## Response Envelope

Every response follows this consistent structure.

### Success

```json
{
  "data": { ... }
}
```

### Success with Pagination

```json
{
  "data": [ ... ],
  "meta": {
    "page": 1,
    "limit": 10,
    "total": 45,
    "totalPages": 5
  }
}
```

### Error

```json
{
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable message",
    "details": null
  }
}
```

### Validation Error (HTTP 422)

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "details": {
      "email": ["The email field is required."],
      "password": ["The password must be at least 8 characters."]
    }
  }
}
```

---

## Error Codes Reference

| HTTP | Code | When |
|------|------|------|
| 400 | `BAD_REQUEST` | Malformed request |
| 401 | `UNAUTHORIZED` | Missing or invalid token |
| 401 | `INVALID_CREDENTIALS` | Wrong email/password |
| 403 | `ACCOUNT_SUSPENDED` | User account is suspended |
| 404 | `NOT_FOUND` | Resource does not exist |
| 422 | `VALIDATION_ERROR` | Field validation failed |
| 429 | `TOO_MANY_REQUESTS` | Rate limit hit (login: 5/min) |
| 500 | `INTERNAL_ERROR` | Server error |

---

## Pagination Query Parameters

All list endpoints accept:

| Param | Default | Description |
|-------|---------|-------------|
| `page` | `1` | Page number |
| `limit` | `10` | Items per page (categories: `50`, free-links: `50`) |
| `q` | — | Search keyword |
| `sort_by` | varies | Column to sort by |
| `sort_dir` | varies | `asc` or `desc` |

---

---

# AUTHENTICATION ENDPOINTS

---

## Register

**`POST /api/mobile/register`** — Public, rate limited (5/min)

### Request Body

```json
{
  "name": "Ahmad Faris",
  "email": "ahmad.faris@gmail.com",
  "password": "Password123",
  "gender": "male",
  "phone": "0123456789",
  "institution": "Universiti Malaya",
  "work_study_status": "studying",
  "country": "Malaysia"
}
```

### Field Rules

| Field | Required | Rules |
|-------|----------|-------|
| `name` | ✅ | string, min 1 char |
| `email` | ✅ | valid email, unique |
| `password` | ✅ | string, min 8 chars |
| `gender` | ✅ | string (e.g. `"male"`, `"female"`) |
| `phone` | ✅ | string |
| `institution` | ✅ | string |
| `work_study_status` | ✅ | `"working"` or `"studying"` |
| `country` | ✅ | string |

### Response `201 Created`

```json
{
  "data": {
    "user": {
      "id": 42,
      "name": "Ahmad Faris",
      "email": "ahmad.faris@gmail.com",
      "photo_url": null,
      "gender": "male",
      "phone": "0123456789",
      "institution": "Universiti Malaya",
      "work_study_status": "studying",
      "country": "Malaysia",
      "status": "active",
      "is_bypassed": false,
      "created_at": "2025-03-30T08:00:00.000000Z",
      "updated_at": "2025-03-30T08:00:00.000000Z",
      "subscription": null,
      "accessible_category_ids": []
    },
    "token": "7|AbCdEfGhIjKlMnOpQrStUvWxYz1234567890abcdef"
  }
}
```

---

## Login

**`POST /api/mobile/login`** — Public, rate limited (5/min)

### Request Body

```json
{
  "email": "ahmad.faris@gmail.com",
  "password": "Password123"
}
```

### Response `200 OK`

```json
{
  "data": {
    "user": {
      "id": 42,
      "name": "Ahmad Faris",
      "email": "ahmad.faris@gmail.com",
      "photo_url": null,
      "gender": "male",
      "phone": "0123456789",
      "institution": "Universiti Malaya",
      "work_study_status": "studying",
      "country": "Malaysia",
      "status": "active",
      "is_bypassed": false,
      "created_at": "2025-03-30T08:00:00.000000Z",
      "updated_at": "2025-03-30T08:00:00.000000Z",
      "subscription": {
        "id": 5,
        "package_id": 2,
        "package_name": "Pro Plan",
        "package_price": "49.90",
        "subscribed_at": "2025-01-01T00:00:00.000000Z",
        "expires_at": "2026-01-01T00:00:00.000000Z",
        "is_active": true,
        "notes": null
      },
      "accessible_category_ids": [1, 2, 3, 5]
    },
    "token": "7|AbCdEfGhIjKlMnOpQrStUvWxYz1234567890abcdef"
  }
}
```

### Errors

```json
// 401 — wrong credentials
{ "error": { "code": "INVALID_CREDENTIALS", "message": "Invalid email or password", "details": null } }

// 403 — account suspended
{ "error": { "code": "ACCOUNT_SUSPENDED", "message": "Your account has been suspended", "details": null } }
```

---

## Get Current User

**`GET /api/mobile/me`** — 🔒 Requires token

### Response `200 OK`

Same user object as login response (with `subscription` and `accessible_category_ids`).

```json
{
  "data": {
    "user": {
      "id": 42,
      "name": "Ahmad Faris",
      "email": "ahmad.faris@gmail.com",
      "photo_url": "/storage/uploads/avatar-42-1711785600.jpg",
      "gender": "male",
      "phone": "0123456789",
      "institution": "Universiti Malaya",
      "work_study_status": "studying",
      "country": "Malaysia",
      "status": "active",
      "is_bypassed": false,
      "created_at": "2025-03-30T08:00:00.000000Z",
      "updated_at": "2025-03-30T08:00:00.000000Z",
      "subscription": {
        "id": 5,
        "package_id": 2,
        "package_name": "Pro Plan",
        "package_price": "49.90",
        "subscribed_at": "2025-01-01T00:00:00.000000Z",
        "expires_at": "2026-01-01T00:00:00.000000Z",
        "is_active": true,
        "notes": null
      },
      "accessible_category_ids": [1, 2, 3, 5]
    }
  }
}
```

---

## Update Profile

**`PUT /api/mobile/me`** — 🔒 Requires token

### Request Body (all fields optional)

```json
{
  "name": "Ahmad Faris Updated",
  "gender": "male",
  "phone": "0199876543",
  "institution": "Universiti Kebangsaan Malaysia",
  "work_study_status": "working",
  "country": "Malaysia"
}
```

### Response `200 OK`

Updated user object (same structure as GET `/me`).

---

## Logout

**`POST /api/mobile/logout`** — 🔒 Requires token

Revokes the current token.

### Response `200 OK`

```json
{
  "data": { "success": true }
}
```

---

## Refresh Token

**`POST /api/mobile/refresh-token`** — 🔒 Requires token

Revokes current token and issues a new one.

### Response `200 OK`

```json
{
  "data": {
    "token": "8|NewTokenStringHere1234567890abcdefgh"
  }
}
```

---

## Revoke All Tokens

**`POST /api/mobile/revoke-all`** — 🔒 Requires token

Logs out from all devices.

### Response `200 OK`

```json
{
  "data": { "success": true }
}
```

---

---

# CONTENT ENDPOINTS

All content endpoints below are read-only for mobile (`GET` only).

---

## Categories (Topics)

**`GET /api/mobile/categories`** — Public (no token needed)

Categories group all content (notes, questions, case summaries, etc.).

### Query Parameters

| Param | Default | Notes |
|-------|---------|-------|
| `page` | `1` | |
| `limit` | `50` | |
| `q` | — | Search by name |
| `type` | — | Filter by type string |
| `sort_by` | `sort_order` | |
| `sort_dir` | `asc` | |

### Response `200 OK`

```json
{
  "data": [
    {
      "id": 1,
      "name": "Land Law",
      "slug": "land-law",
      "description": "Topics covering the National Land Code and related legislation",
      "type": "notes",
      "icon_url": null,
      "sort_order": 1,
      "legal_basis": "National Land Code 1965",
      "created_at": "2025-01-15T00:00:00.000000Z",
      "updated_at": "2025-01-15T00:00:00.000000Z"
    },
    {
      "id": 2,
      "name": "Contract Law",
      "slug": "contract-law",
      "description": "Topics covering the Contracts Act 1950",
      "type": "notes",
      "icon_url": null,
      "sort_order": 2,
      "legal_basis": "Contracts Act 1950",
      "created_at": "2025-01-15T00:00:00.000000Z",
      "updated_at": "2025-01-15T00:00:00.000000Z"
    }
  ],
  "meta": {
    "page": 1,
    "limit": 50,
    "total": 12,
    "totalPages": 1
  }
}
```

### Get Single Category

**`GET /api/mobile/categories/{id}`**

```json
{
  "data": {
    "id": 1,
    "name": "Land Law",
    "slug": "land-law",
    "description": "Topics covering the National Land Code and related legislation",
    "type": "notes",
    "icon_url": null,
    "sort_order": 1,
    "legal_basis": "National Land Code 1965",
    "created_at": "2025-01-15T00:00:00.000000Z",
    "updated_at": "2025-01-15T00:00:00.000000Z"
  }
}
```

---

## Notes

**`GET /api/mobile/notes`** — 🔒 Requires token

Notes are PDF/image study materials grouped by category.

### Query Parameters

| Param | Default | Notes |
|-------|---------|-------|
| `page` | `1` | |
| `limit` | `10` | |
| `q` | — | Search by title |
| `category_id` | — | Filter by category |
| `sort_by` | `sort_order` | |
| `sort_dir` | `asc` | |

### Response `200 OK`

```json
{
  "data": [
    {
      "id": 1,
      "title": "Introduction to Land Law",
      "description": "Overview of the National Land Code provisions",
      "category_id": 1,
      "file_path": "notes/introduction-to-land-law.pdf",
      "file_name": "introduction-to-land-law.pdf",
      "file_type": "application/pdf",
      "file_size": 204800,
      "sort_order": 1,
      "is_published": true,
      "created_at": "2025-01-20T00:00:00.000000Z",
      "updated_at": "2025-01-20T00:00:00.000000Z",
      "category": {
        "id": 1,
        "name": "Land Law",
        "slug": "land-law",
        "type": "notes"
      }
    }
  ],
  "meta": {
    "page": 1,
    "limit": 10,
    "total": 28,
    "totalPages": 3
  }
}
```

### Get Single Note

**`GET /api/mobile/notes/{id}`** — 🔒 Requires token

```json
{
  "data": {
    "id": 1,
    "title": "Introduction to Land Law",
    "description": "Overview of the National Land Code provisions",
    "category_id": 1,
    "file_path": "notes/introduction-to-land-law.pdf",
    "file_name": "introduction-to-land-law.pdf",
    "file_type": "application/pdf",
    "file_size": 204800,
    "sort_order": 1,
    "is_published": true,
    "created_at": "2025-01-20T00:00:00.000000Z",
    "updated_at": "2025-01-20T00:00:00.000000Z",
    "category": {
      "id": 1,
      "name": "Land Law",
      "slug": "land-law",
      "description": "Topics covering the National Land Code and related legislation",
      "type": "notes",
      "sort_order": 1
    }
  }
}
```

> **File URL**: Prepend `https://lex-api.0w0.my/storage/` to `file_path` to get the full downloadable URL.
> Example: `https://lex-api.0w0.my/storage/notes/introduction-to-land-law.pdf`

---

## Case Summaries

**`GET /api/mobile/case-summaries`** — 🔒 Requires token

### Query Parameters

| Param | Default | Notes |
|-------|---------|-------|
| `page` | `1` | |
| `limit` | `10` | |
| `q` | — | Search by title or citation |
| `category_id` | — | Filter by category |
| `sort_by` | `created_at` | |
| `sort_dir` | `desc` | |

### Response `200 OK`

```json
{
  "data": [
    {
      "id": 1,
      "title": "Adorna Properties Sdn Bhd v Boonsom Boonyanit",
      "citation": "[2001] 1 MLJ 241",
      "summary_text": "This case concerns the doctrine of indefeasibility of title under the National Land Code. The Federal Court held that a bona fide purchaser for value without notice obtains an indefeasible title upon registration even if the vendor's title was obtained by fraud.",
      "category_id": 1,
      "pdf_file_path": null,
      "is_published": true,
      "created_at": "2025-02-01T00:00:00.000000Z",
      "updated_at": "2025-02-01T00:00:00.000000Z",
      "category": {
        "id": 1,
        "name": "Land Law",
        "slug": "land-law",
        "type": "notes"
      }
    }
  ],
  "meta": {
    "page": 1,
    "limit": 10,
    "total": 65,
    "totalPages": 7
  }
}
```

### Get Single Case Summary

**`GET /api/mobile/case-summaries/{id}`** — 🔒 Requires token

```json
{
  "data": {
    "id": 1,
    "title": "Adorna Properties Sdn Bhd v Boonsom Boonyanit",
    "citation": "[2001] 1 MLJ 241",
    "summary_text": "This case concerns the doctrine of indefeasibility of title under the National Land Code. The Federal Court held that a bona fide purchaser for value without notice obtains an indefeasible title upon registration even if the vendor's title was obtained by fraud.",
    "category_id": 1,
    "pdf_file_path": "case-summaries/adorna-properties.pdf",
    "is_published": true,
    "created_at": "2025-02-01T00:00:00.000000Z",
    "updated_at": "2025-02-01T00:00:00.000000Z",
    "category": {
      "id": 1,
      "name": "Land Law",
      "slug": "land-law"
    }
  }
}
```

---

## Questions (MCQ)

**`GET /api/mobile/questions`** — 🔒 Requires token

### Query Parameters

| Param | Default | Notes |
|-------|---------|-------|
| `page` | `1` | |
| `limit` | `10` | |
| `q` | — | Search by question text |
| `category_id` | — | Filter by category |
| `sort_by` | `sort_order` | |
| `sort_dir` | `asc` | |

### Response `200 OK`

```json
{
  "data": [
    {
      "id": 1,
      "category_id": 1,
      "question_text": "Under the National Land Code, which of the following is NOT a type of alienated land?",
      "options": [
        "Freehold land",
        "Leasehold land",
        "Reserved land",
        "Country land"
      ],
      "correct_option_index": 2,
      "explanation": "Reserved land under Section 62 of the NLC is land reserved for public purposes and cannot be alienated. Freehold, leasehold, and country land are all forms of alienated land.",
      "sort_order": 1,
      "is_published": true,
      "created_at": "2025-02-10T00:00:00.000000Z",
      "updated_at": "2025-02-10T00:00:00.000000Z",
      "category": {
        "id": 1,
        "name": "Land Law",
        "slug": "land-law"
      }
    }
  ],
  "meta": {
    "page": 1,
    "limit": 10,
    "total": 150,
    "totalPages": 15
  }
}
```

> `options` is always an array of exactly **4 strings**.
> `correct_option_index` is `0`-based (0 = first option, 1 = second, etc.).

### Get Single Question

**`GET /api/mobile/questions/{id}`** — 🔒 Requires token

Same structure as list item above.

---

## Question Papers (Past Year / Topical)

**`GET /api/mobile/question-papers`** — 🔒 Requires token

### Query Parameters

| Param | Default | Notes |
|-------|---------|-------|
| `page` | `1` | |
| `limit` | `10` | |
| `q` | — | Search by title |
| `type` | — | `"past_year"` or `"topical"` |
| `category_id` | — | Filter by category |
| `sort_by` | `sort_order` | |
| `sort_dir` | `asc` | |

### Response `200 OK`

```json
{
  "data": [
    {
      "id": 1,
      "title": "Land Law Past Year 2023",
      "slug": "land-law-past-year-2023",
      "type": "past_year",
      "year": 2023,
      "category_id": 1,
      "description": "Full past year examination paper for Land Law 2023",
      "file_path": "question-papers/land-law-2023.pdf",
      "file_name": "land-law-2023.pdf",
      "file_type": "application/pdf",
      "file_size": 512000,
      "is_published": true,
      "sort_order": 1,
      "created_at": "2025-01-25T00:00:00.000000Z",
      "updated_at": "2025-01-25T00:00:00.000000Z",
      "category": {
        "id": 1,
        "name": "Land Law",
        "slug": "land-law"
      }
    }
  ],
  "meta": {
    "page": 1,
    "limit": 10,
    "total": 22,
    "totalPages": 3
  }
}
```

### Get Single Question Paper

**`GET /api/mobile/question-papers/{id}`** — 🔒 Requires token

Same structure as list item above.

> **File URL**: `https://lex-api.0w0.my/storage/` + `file_path`

---

## Statutes

**`GET /api/mobile/statutes`** — 🔒 Requires token

### Query Parameters

| Param | Default | Notes |
|-------|---------|-------|
| `page` | `1` | |
| `limit` | `10` | |
| `q` | — | Search by title |
| `type` | — | `"link"` or `"document"` |
| `sort_by` | `sort_order` | |
| `sort_dir` | `asc` | |

### Response `200 OK`

```json
{
  "data": [
    {
      "id": 1,
      "title": "National Land Code 1965 (Act 56)",
      "slug": "national-land-code-1965",
      "type": "link",
      "url": "https://www.agc.gov.my/agcportal/uploads/files/Publications/LOM/EN/Act%2056.pdf",
      "description": "The principal legislation governing land law in Peninsular Malaysia",
      "file_path": null,
      "file_name": null,
      "file_type": null,
      "file_size": null,
      "is_published": true,
      "sort_order": 1,
      "created_at": "2025-01-10T00:00:00.000000Z",
      "updated_at": "2025-01-10T00:00:00.000000Z"
    },
    {
      "id": 2,
      "title": "Contracts Act 1950 (Act 136)",
      "slug": "contracts-act-1950",
      "type": "document",
      "url": null,
      "description": "The main statute governing contract law in Malaysia",
      "file_path": "statutes/contracts-act-1950.pdf",
      "file_name": "contracts-act-1950.pdf",
      "file_type": "application/pdf",
      "file_size": 1048576,
      "is_published": true,
      "sort_order": 2,
      "created_at": "2025-01-10T00:00:00.000000Z",
      "updated_at": "2025-01-10T00:00:00.000000Z"
    }
  ],
  "meta": {
    "page": 1,
    "limit": 10,
    "total": 18,
    "totalPages": 2
  }
}
```

> If `type` is `"link"` → use `url` directly.
> If `type` is `"document"` → file URL is `https://lex-api.0w0.my/storage/` + `file_path`.

### Get Single Statute

**`GET /api/mobile/statutes/{id}`** — 🔒 Requires token

Same structure as list item above.

---

## Free Links (Case Law Resources)

**`GET /api/mobile/free-links`** — 🔒 Requires token

External links to legal databases (e-LJ, CLJ, MLJU, etc.).

### Query Parameters

| Param | Default | Notes |
|-------|---------|-------|
| `page` | `1` | |
| `limit` | `50` | |
| `q` | — | Search by title |
| `sort_by` | `sort_order` | |
| `sort_dir` | `asc` | |

### Response `200 OK`

```json
{
  "data": [
    {
      "id": 1,
      "title": "e-LJ (Electronic Law Journal)",
      "url": "https://www.elj.com.my",
      "icon_image_path": null,
      "sort_order": 1,
      "is_active": true,
      "created_at": "2025-01-05T00:00:00.000000Z",
      "updated_at": "2025-01-05T00:00:00.000000Z"
    },
    {
      "id": 2,
      "title": "Current Law Journal (CLJ)",
      "url": "https://www.lawyerline.com.my",
      "icon_image_path": "/storage/uploads/clj-logo.png",
      "sort_order": 2,
      "is_active": true,
      "created_at": "2025-01-05T00:00:00.000000Z",
      "updated_at": "2025-01-05T00:00:00.000000Z"
    }
  ],
  "meta": {
    "page": 1,
    "limit": 50,
    "total": 6,
    "totalPages": 1
  }
}
```

---

## Packages (Subscription Plans)

**`GET /api/mobile/packages`** — Public (no token needed)

### Response `200 OK`

```json
{
  "data": [
    {
      "id": 1,
      "name": "Basic Plan",
      "description": "Access to notes and questions for core subjects",
      "price": "19.90",
      "duration_months": 1,
      "chatbot_access": false,
      "accessible_category_ids": [1, 2, 3],
      "is_active": true,
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-01-01T00:00:00.000000Z"
    },
    {
      "id": 2,
      "name": "Pro Plan",
      "description": "Full access to all subjects, case summaries, past year papers, and AI chatbot",
      "price": "49.90",
      "duration_months": 1,
      "chatbot_access": true,
      "accessible_category_ids": [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
      "is_active": true,
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-01-01T00:00:00.000000Z"
    },
    {
      "id": 3,
      "name": "Annual Pro",
      "description": "Full access for a full year — best value",
      "price": "399.00",
      "duration_months": 12,
      "chatbot_access": true,
      "accessible_category_ids": [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
      "is_active": true,
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-01-01T00:00:00.000000Z"
    }
  ],
  "meta": {
    "page": 1,
    "limit": 10,
    "total": 3,
    "totalPages": 1
  }
}
```

### Get Single Package

**`GET /api/mobile/packages/{id}`** — Public

Same structure as list item above.

---

## App Settings

**`GET /api/settings`** — Public (no token needed)

Returns global app configuration.

### Response `200 OK`

```json
{
  "data": {
    "siteTitle": "LexSZA",
    "tagline": "Your Legal Study Companion",
    "siteIconUrl": null,
    "sidebarLogoUrl": null,
    "faviconUrl": null,
    "language": "en",
    "timezone": "Asia/Kuala_Lumpur",
    "footerText": "© 2025 LexSZA. All rights reserved.",
    "companyName": "LexSZA Sdn Bhd",
    "companyAddress": "Kuala Lumpur, Malaysia",
    "brandColor": "#6366f1",
    "aboutContent": "",
    "maintenanceMode": "false",
    "supportEmail": "support@lexsza.com",
    "supportPhone": null,
    "websiteUrl": null,
    "facebookUrl": null,
    "instagramUrl": null,
    "twitterUrl": null,
    "linkedinUrl": null,
    "termsUrl": null,
    "privacyUrl": null,
    "appName": "LexSZA",
    "upgradePromptMessage": "Upgrade your plan to access this content.",
    "minimumAppVersion": "1.0.0",
    "appStoreUrl": null,
    "googlePlayUrl": null
  }
}
```

---

---

# ACCESS CONTROL

## Content Access Logic

When a user logs in, check their `subscription` and `accessible_category_ids`:

```
user.subscription === null              → No active subscription (show upgrade prompt)
user.subscription.is_active === false   → Expired subscription (show renew prompt)
user.is_bypassed === true               → Admin bypass, skip all access checks

To check access to a category:
  user.accessible_category_ids.includes(categoryId)  → Has access
```

Use `user.accessible_category_ids` (from login/me response) to gate content. If a user tries to view a note in a category they don't have access to, show the upgrade prompt with the message from settings `upgradePromptMessage`.

---

## Subscription Object

Returned inside the `user` object on login and GET `/me`:

```json
{
  "id": 5,
  "package_id": 2,
  "package_name": "Pro Plan",
  "package_price": "49.90",
  "subscribed_at": "2025-01-01T00:00:00.000000Z",
  "expires_at": "2026-01-01T00:00:00.000000Z",
  "is_active": true,
  "notes": null
}
```

| Field | Type | Notes |
|-------|------|-------|
| `is_active` | boolean | `true` = current date is before `expires_at` |
| `expires_at` | ISO 8601 datetime | Use this to show "expires in X days" |
| `package_price` | string (decimal) | Snapshot of price at time of purchase |

---

---

# COMPLETE ENDPOINT LIST

## Mobile Endpoints (prefix: `/api/mobile/`)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/register` | Public | Register new user |
| POST | `/login` | Public | Login, returns token |
| POST | `/logout` | 🔒 Token | Logout current device |
| POST | `/refresh-token` | 🔒 Token | Get new token |
| POST | `/revoke-all` | 🔒 Token | Logout all devices |
| GET | `/me` | 🔒 Token | Get current user |
| PUT | `/me` | 🔒 Token | Update profile |
| GET | `/categories` | Public | List all topics |
| GET | `/categories/{id}` | Public | Get single topic |
| GET | `/notes` | 🔒 Token | List notes |
| GET | `/notes/{id}` | 🔒 Token | Get single note |
| GET | `/case-summaries` | 🔒 Token | List case summaries |
| GET | `/case-summaries/{id}` | 🔒 Token | Get single case summary |
| GET | `/questions` | 🔒 Token | List MCQ questions |
| GET | `/questions/{id}` | 🔒 Token | Get single question |
| GET | `/question-papers` | 🔒 Token | List past year / topical papers |
| GET | `/question-papers/{id}` | 🔒 Token | Get single paper |
| GET | `/statutes` | 🔒 Token | List statutes |
| GET | `/statutes/{id}` | 🔒 Token | Get single statute |
| GET | `/free-links` | 🔒 Token | List case law links |
| GET | `/packages` | Public | List subscription packages |
| GET | `/packages/{id}` | Public | Get single package |

## General Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/settings` | Public | Get app settings |

---

---

# IMPLEMENTATION NOTES FOR MOBILE

## 1. Token Storage

Store the token securely (e.g. Flutter SecureStorage, React Native Keychain). Send it on every request:

```
Authorization: Bearer 7|AbCdEfGhIjKlMnOpQrStUvWxYz1234567890abcdef
```

## 2. File URLs

Two types of files exist:

| Type | How to get URL |
|------|---------------|
| Uploaded files (notes, papers, statutes) | `https://lex-api.0w0.my/storage/` + `file_path` field |
| External links (statutes type=link, free-links) | Use `url` field directly |
| User avatars | `photo_url` is already an absolute path like `/storage/uploads/...` — prepend base URL |

Full avatar URL: `https://lex-api.0w0.my` + `photo_url`

## 3. Pagination

Always implement pagination. Use `meta.totalPages` to know when to stop fetching:

```
if (meta.page < meta.totalPages) → load more
```

## 4. Content Gating

Check `accessible_category_ids` on every content screen:

```
notes.category_id is in user.accessible_category_ids  → show content
otherwise                                               → show upgrade prompt
```

`is_bypassed: true` means skip all subscription checks (admin/test users).

## 5. Question Display

- `options` array always has exactly 4 items (index 0–3)
- `correct_option_index` is 0-based
- Only reveal correct answer after user submits

## 6. Statute Types

- `type: "link"` → open `url` in browser/webview
- `type: "document"` → download/open `file_path` from storage

## 7. Rate Limits

- Login & Register: **5 requests/minute** per IP → show "Too many attempts, try again later" on 429

## 8. Date Format

All timestamps are ISO 8601 UTC: `"2025-03-30T08:00:00.000000Z"`
Server timezone is `Asia/Kuala_Lumpur` (UTC+8) but datetimes are stored as UTC.

## 9. Refresh on App Resume

Call `GET /api/mobile/me` when the app resumes from background to refresh subscription status and accessible categories.
