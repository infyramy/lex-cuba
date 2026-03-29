<script setup lang="ts">
import { ref, computed } from "vue";
import { BookOpen, Search, X } from "lucide-vue-next";
import AdminLayout from "@/layouts/AdminLayout.vue";

/* ------------------------------------------------------------------ */
/*  Types                                                              */
/* ------------------------------------------------------------------ */

type HttpMethod = "GET" | "POST" | "PUT" | "DELETE";

interface Param {
  name: string;
  type: string;
  required: boolean;
  description: string;
}

interface Endpoint {
  method: HttpMethod;
  path: string;
  auth: "Public" | "Auth Required" | string; // string for "Auth + Permission: xxx.yyy"
  description: string;
  params?: Param[];
  responseExample?: string;
  note?: string;
}

interface EndpointGroup {
  id: string;
  title: string;
  description: string;
  endpoints: Endpoint[];
}

/* ------------------------------------------------------------------ */
/*  Filters                                                            */
/* ------------------------------------------------------------------ */

const searchQuery = ref("");
const activeMethodFilter = ref<HttpMethod | "ALL">("ALL");

const methodFilters: { label: string; value: HttpMethod | "ALL"; cls: string }[] = [
  { label: "ALL", value: "ALL", cls: "bg-slate-100 text-slate-700 hover:bg-slate-200" },
  { label: "GET", value: "GET", cls: "bg-emerald-100 text-emerald-700 hover:bg-emerald-200" },
  { label: "POST", value: "POST", cls: "bg-blue-100 text-blue-700 hover:bg-blue-200" },
  { label: "PUT", value: "PUT", cls: "bg-amber-100 text-amber-700 hover:bg-amber-200" },
  { label: "DELETE", value: "DELETE", cls: "bg-rose-100 text-rose-700 hover:bg-rose-200" },
];

const methodBadge: Record<HttpMethod, string> = {
  GET: "bg-emerald-100 text-emerald-700 ring-emerald-300",
  POST: "bg-blue-100 text-blue-700 ring-blue-300",
  PUT: "bg-amber-100 text-amber-700 ring-amber-300",
  DELETE: "bg-rose-100 text-rose-700 ring-rose-300",
};

/* ------------------------------------------------------------------ */
/*  Helper: standard pagination query params                          */
/* ------------------------------------------------------------------ */

const paginationParams: Param[] = [
  { name: "page", type: "integer", required: false, description: "Page number (default: 1)" },
  { name: "limit", type: "integer", required: false, description: "Items per page (default: 10)" },
  { name: "q", type: "string", required: false, description: "Search keyword" },
  { name: "sortBy", type: "string", required: false, description: "Column to sort by (default: created_at)" },
  { name: "sortDir", type: "string", required: false, description: "Sort direction: asc | desc (default: desc)" },
];

/* ------------------------------------------------------------------ */
/*  Endpoint groups                                                    */
/* ------------------------------------------------------------------ */

const groups: EndpointGroup[] = [
  /* ── Auth (SPA session-based) ─────────────────────────────────── */
  {
    id: "auth",
    title: "Auth (SPA Session-Based)",
    description: "Session-based authentication for the admin SPA. Uses Sanctum XSRF cookie flow.",
    endpoints: [
      {
        method: "POST",
        path: "/api/auth/login",
        auth: "Public",
        description: "Authenticate with email and password. Sets Sanctum session cookie. Rate-limited.",
        params: [
          { name: "email", type: "string", required: true, description: "User email address" },
          { name: "password", type: "string", required: true, description: "Password (min 6 chars)" },
        ],
        responseExample: `{ "data": { "user": { "id": 1, "name": "Admin", "email": "admin@example.com", "role": "admin", "permissions": [...] } } }`,
      },
      {
        method: "POST",
        path: "/api/auth/logout",
        auth: "Auth Required",
        description: "Invalidate the current session and clear cookies.",
        responseExample: `{ "data": { "success": true } }`,
      },
      {
        method: "GET",
        path: "/api/auth/me",
        auth: "Auth Required",
        description: "Get the currently authenticated user's profile and permissions.",
        responseExample: `{ "data": { "user": { "id": 1, "name": "Admin", "email": "admin@example.com", "role": "admin", "permissions": [...] } } }`,
      },
      {
        method: "PUT",
        path: "/api/auth/me",
        auth: "Auth Required",
        description: "Update the authenticated user's own name and/or email.",
        params: [
          { name: "name", type: "string", required: false, description: "New display name" },
          { name: "email", type: "string", required: false, description: "New email address" },
        ],
        responseExample: `{ "data": { "user": { "id": 1, "name": "Updated", "email": "new@example.com" } } }`,
      },
      {
        method: "POST",
        path: "/api/auth/password",
        auth: "Auth Required",
        description: "Change the authenticated user's password.",
        params: [
          { name: "currentPassword", type: "string", required: true, description: "Current password" },
          { name: "newPassword", type: "string", required: true, description: "New password (min 6 chars)" },
        ],
        responseExample: `{ "data": { "success": true } }`,
      },
      {
        method: "POST",
        path: "/api/auth/avatar",
        auth: "Auth Required",
        description: "Upload a profile avatar image. Multipart form data. Rate-limited.",
        params: [
          { name: "file", type: "file", required: true, description: "Image file (png, jpg, jpeg, gif, webp; max 2 MB)" },
        ],
        responseExample: `{ "data": { "photoUrl": "/storage/avatars/abc123.jpg" } }`,
      },
      {
        method: "DELETE",
        path: "/api/auth/avatar",
        auth: "Auth Required",
        description: "Remove the authenticated user's avatar.",
        responseExample: `{ "data": { "success": true } }`,
      },
    ],
  },

  /* ── Mobile Auth (token-based) ────────────────────────────────── */
  {
    id: "mobile-auth",
    title: "Mobile Auth (Token-Based)",
    description: "Token-based authentication for mobile app clients. Uses Bearer token in Authorization header.",
    endpoints: [
      {
        method: "POST",
        path: "/api/mobile/register",
        auth: "Public",
        description: "Register a new member account for the mobile app. Rate-limited.",
        params: [
          { name: "name", type: "string", required: true, description: "Full name" },
          { name: "email", type: "string", required: true, description: "Email (must be unique)" },
          { name: "password", type: "string", required: true, description: "Password (min 6 chars)" },
          { name: "gender", type: "string", required: false, description: "Gender" },
          { name: "phone", type: "string", required: false, description: "Phone number" },
          { name: "institution", type: "string", required: false, description: "Institution name" },
          { name: "workStudyStatus", type: "string", required: false, description: "working | studying" },
          { name: "country", type: "string", required: false, description: "Country" },
        ],
        responseExample: `{ "data": { "user": { "id": 5, "name": "John", "email": "john@example.com", ... }, "token": "1|abc123..." } }`,
      },
      {
        method: "POST",
        path: "/api/mobile/login",
        auth: "Public",
        description: "Authenticate a member and receive a Bearer token. Rate-limited.",
        params: [
          { name: "email", type: "string", required: true, description: "Member email" },
          { name: "password", type: "string", required: true, description: "Password (min 6 chars)" },
        ],
        responseExample: `{ "data": { "user": { "id": 5, "name": "John", ... }, "token": "1|abc123..." } }`,
      },
      {
        method: "GET",
        path: "/api/mobile/me",
        auth: "Auth Required",
        description: "Get the authenticated member's profile including subscription info.",
        note: "Bearer token required.",
        responseExample: `{ "data": { "user": { "id": 5, "name": "John", "subscription": { ... } } } }`,
      },
      {
        method: "PUT",
        path: "/api/mobile/me",
        auth: "Auth Required",
        description: "Update the authenticated member's profile fields.",
        note: "Bearer token required.",
        params: [
          { name: "name", type: "string", required: false, description: "Display name" },
          { name: "gender", type: "string", required: false, description: "Gender" },
          { name: "phone", type: "string", required: false, description: "Phone number" },
          { name: "institution", type: "string", required: false, description: "Institution name" },
          { name: "workStudyStatus", type: "string", required: false, description: "working | studying" },
          { name: "country", type: "string", required: false, description: "Country" },
        ],
        responseExample: `{ "data": { "user": { "id": 5, "name": "Updated Name", ... } } }`,
      },
      {
        method: "POST",
        path: "/api/mobile/logout",
        auth: "Auth Required",
        description: "Revoke the current Bearer token.",
        note: "Bearer token required.",
        responseExample: `{ "data": { "success": true } }`,
      },
      {
        method: "POST",
        path: "/api/mobile/refresh-token",
        auth: "Auth Required",
        description: "Revoke the current token and issue a new one.",
        note: "Bearer token required.",
        responseExample: `{ "data": { "token": "2|xyz789..." } }`,
      },
      {
        method: "POST",
        path: "/api/mobile/revoke-all",
        auth: "Auth Required",
        description: "Revoke ALL tokens for the authenticated user (logout everywhere).",
        note: "Bearer token required.",
        responseExample: `{ "data": { "success": true } }`,
      },
    ],
  },

  /* ── Mobile Content (read-only) ───────────────────────────────── */
  {
    id: "mobile-content",
    title: "Mobile Content (Read-Only)",
    description: "Read-only content endpoints available to authenticated mobile members. All require Bearer token. No permission middleware -- any authenticated member can access.",
    endpoints: [
      {
        method: "GET",
        path: "/api/mobile/categories",
        auth: "Auth Required",
        description: "List all topics/categories.",
        params: [...paginationParams],
        responseExample: `{ "data": [...], "meta": { "page": 1, "limit": 10, "total": 25, "totalPages": 3 } }`,
      },
      {
        method: "GET",
        path: "/api/mobile/categories/{id}",
        auth: "Auth Required",
        description: "Get a single category/topic by ID.",
        responseExample: `{ "data": { "id": 1, "name": "Contract Law", "slug": "contract-law", ... } }`,
      },
      {
        method: "GET",
        path: "/api/mobile/notes",
        auth: "Auth Required",
        description: "List notes. Supports filtering by categoryId.",
        params: [...paginationParams, { name: "categoryId", type: "integer", required: false, description: "Filter by category" }],
        responseExample: `{ "data": [...], "meta": { ... } }`,
      },
      {
        method: "GET",
        path: "/api/mobile/notes/{id}",
        auth: "Auth Required",
        description: "Get a single note with file info.",
        responseExample: `{ "data": { "id": 1, "title": "Note Title", "filePath": "...", ... } }`,
      },
      {
        method: "GET",
        path: "/api/mobile/case-summaries",
        auth: "Auth Required",
        description: "List case summaries. Supports filtering by categoryId.",
        params: [...paginationParams, { name: "categoryId", type: "integer", required: false, description: "Filter by category" }],
        responseExample: `{ "data": [...], "meta": { ... } }`,
      },
      {
        method: "GET",
        path: "/api/mobile/case-summaries/{id}",
        auth: "Auth Required",
        description: "Get a single case summary.",
        responseExample: `{ "data": { "id": 1, "title": "...", "citation": "...", "summaryText": "..." } }`,
      },
      {
        method: "GET",
        path: "/api/mobile/questions",
        auth: "Auth Required",
        description: "List MCQ questions. Supports filtering by categoryId.",
        params: [...paginationParams, { name: "categoryId", type: "integer", required: false, description: "Filter by category" }],
        responseExample: `{ "data": [...], "meta": { ... } }`,
      },
      {
        method: "GET",
        path: "/api/mobile/questions/{id}",
        auth: "Auth Required",
        description: "Get a single MCQ question with options.",
        responseExample: `{ "data": { "id": 1, "questionText": "...", "options": ["A","B","C","D"], "correctOptionIndex": 2 } }`,
      },
      {
        method: "GET",
        path: "/api/mobile/question-papers",
        auth: "Auth Required",
        description: "List question papers.",
        params: [...paginationParams],
        responseExample: `{ "data": [...], "meta": { ... } }`,
      },
      {
        method: "GET",
        path: "/api/mobile/question-papers/{id}",
        auth: "Auth Required",
        description: "Get a single question paper with file info.",
        responseExample: `{ "data": { "id": 1, "title": "...", "type": "past_year", "filePath": "..." } }`,
      },
      {
        method: "GET",
        path: "/api/mobile/statutes",
        auth: "Auth Required",
        description: "List statutes.",
        params: [...paginationParams],
        responseExample: `{ "data": [...], "meta": { ... } }`,
      },
      {
        method: "GET",
        path: "/api/mobile/statutes/{id}",
        auth: "Auth Required",
        description: "Get a single statute.",
        responseExample: `{ "data": { "id": 1, "title": "...", "type": "link", "url": "..." } }`,
      },
      {
        method: "GET",
        path: "/api/mobile/free-links",
        auth: "Auth Required",
        description: "List free directory links.",
        params: [...paginationParams],
        responseExample: `{ "data": [...], "meta": { ... } }`,
      },
      {
        method: "GET",
        path: "/api/mobile/packages",
        auth: "Auth Required",
        description: "List available subscription packages.",
        params: [...paginationParams],
        responseExample: `{ "data": [...], "meta": { ... } }`,
      },
      {
        method: "GET",
        path: "/api/mobile/packages/{id}",
        auth: "Auth Required",
        description: "Get a single subscription package.",
        responseExample: `{ "data": { "id": 1, "name": "Premium", "price": 99.00, "durationMonths": 12 } }`,
      },
    ],
  },

  /* ── Settings (public) ────────────────────────────────────────── */
  {
    id: "settings",
    title: "Settings",
    description: "Application-wide settings. GET is public (used by SPA before auth). PUT requires admin auth.",
    endpoints: [
      {
        method: "GET",
        path: "/api/settings",
        auth: "Public",
        description: "Get all app-wide settings (site title, branding, etc.).",
        responseExample: `{ "data": { "siteTitle": "CORRAD", "siteLogo": "/storage/...", ... } }`,
      },
      {
        method: "PUT",
        path: "/api/settings",
        auth: "Auth + Permission: settings.edit",
        description: "Update app-wide settings. Accepts key-value pairs.",
        params: [
          { name: "*", type: "mixed", required: false, description: "Any setting key-value pairs to update" },
        ],
        responseExample: `{ "data": { "siteTitle": "Updated Title", ... } }`,
      },
    ],
  },

  /* ── Members ──────────────────────────────────────────────────── */
  {
    id: "members",
    title: "Members",
    description: "Manage mobile app member accounts. Members are users with user_type = 'member'.",
    endpoints: [
      {
        method: "GET",
        path: "/api/members",
        auth: "Auth + Permission: members.view",
        description: "List members with pagination and search.",
        params: [...paginationParams],
        responseExample: `{ "data": [...], "meta": { "page": 1, "limit": 10, "total": 150, "totalPages": 15 } }`,
      },
      {
        method: "POST",
        path: "/api/members",
        auth: "Auth + Permission: members.create",
        description: "Create a new member account.",
        params: [
          { name: "name", type: "string", required: true, description: "Full name" },
          { name: "email", type: "string", required: true, description: "Email (unique)" },
          { name: "password", type: "string", required: true, description: "Password (min 6)" },
          { name: "gender", type: "string", required: false, description: "Gender" },
          { name: "phone", type: "string", required: false, description: "Phone number" },
          { name: "institution", type: "string", required: false, description: "Institution name" },
          { name: "workStudyStatus", type: "string", required: false, description: "working | studying" },
          { name: "country", type: "string", required: false, description: "Country" },
          { name: "status", type: "string", required: false, description: "active | suspended" },
          { name: "isBypassed", type: "boolean", required: false, description: "Bypass subscription check" },
        ],
        responseExample: `{ "data": { "id": 5, "name": "Jane", "email": "jane@example.com", ... } }`,
      },
      {
        method: "GET",
        path: "/api/members/{id}",
        auth: "Auth + Permission: members.view",
        description: "Get a single member with subscription details.",
        responseExample: `{ "data": { "id": 5, "name": "Jane", "subscription": { ... }, ... } }`,
      },
      {
        method: "PUT",
        path: "/api/members/{id}",
        auth: "Auth + Permission: members.edit",
        description: "Update member profile fields.",
        params: [
          { name: "name", type: "string", required: false, description: "Full name" },
          { name: "email", type: "string", required: false, description: "Email (unique)" },
          { name: "password", type: "string", required: false, description: "New password (min 6)" },
          { name: "gender", type: "string", required: false, description: "Gender" },
          { name: "phone", type: "string", required: false, description: "Phone number" },
          { name: "institution", type: "string", required: false, description: "Institution name" },
          { name: "workStudyStatus", type: "string", required: false, description: "working | studying" },
          { name: "country", type: "string", required: false, description: "Country" },
          { name: "status", type: "string", required: false, description: "active | suspended" },
          { name: "isBypassed", type: "boolean", required: false, description: "Bypass subscription check" },
        ],
        responseExample: `{ "data": { "id": 5, "name": "Updated", ... } }`,
      },
      {
        method: "DELETE",
        path: "/api/members/{id}",
        auth: "Auth + Permission: members.delete",
        description: "Delete a member account.",
        responseExample: `{ "data": { "success": true } }`,
      },
      {
        method: "PUT",
        path: "/api/members/{id}/status",
        auth: "Auth + Permission: members.edit",
        description: "Set a member's account status.",
        params: [
          { name: "status", type: "string", required: true, description: "active | suspended" },
        ],
        responseExample: `{ "data": { "id": 5, "status": "suspended", ... } }`,
      },
    ],
  },

  /* ── Member Subscriptions ─────────────────────────────────────── */
  {
    id: "member-subscriptions",
    title: "Member Subscriptions",
    description: "Manage subscription assignments for individual members.",
    endpoints: [
      {
        method: "GET",
        path: "/api/members/{memberId}/subscription",
        auth: "Auth + Permission: members.view",
        description: "Get the member's current active subscription.",
        responseExample: `{ "data": { "id": 1, "packageId": 2, "subscribedAt": "2025-01-01", "expiresAt": "2026-01-01" } }`,
      },
      {
        method: "POST",
        path: "/api/members/{memberId}/subscription",
        auth: "Auth + Permission: members.edit",
        description: "Assign or replace the member's subscription.",
        params: [
          { name: "packageId", type: "integer", required: true, description: "Package ID (must exist)" },
          { name: "subscribedAt", type: "date", required: false, description: "Subscription start date (ISO format)" },
          { name: "expiresAt", type: "date", required: false, description: "Expiry date (must be after subscribedAt)" },
          { name: "notes", type: "string", required: false, description: "Admin notes (max 500 chars)" },
        ],
        responseExample: `{ "data": { "id": 3, "packageId": 2, "subscribedAt": "2025-06-01", ... } }`,
      },
      {
        method: "DELETE",
        path: "/api/members/{memberId}/subscription",
        auth: "Auth + Permission: members.edit",
        description: "Remove the member's subscription.",
        responseExample: `{ "data": { "success": true } }`,
      },
    ],
  },

  /* ── Packages ─────────────────────────────────────────────────── */
  {
    id: "packages",
    title: "Packages",
    description: "Manage subscription packages available to members.",
    endpoints: [
      {
        method: "GET",
        path: "/api/packages",
        auth: "Auth + Permission: packages.view",
        description: "List subscription packages.",
        params: [...paginationParams],
        responseExample: `{ "data": [...], "meta": { ... } }`,
      },
      {
        method: "POST",
        path: "/api/packages",
        auth: "Auth + Permission: packages.create",
        description: "Create a subscription package.",
        params: [
          { name: "name", type: "string", required: true, description: "Package name" },
          { name: "description", type: "string", required: false, description: "Description" },
          { name: "price", type: "number", required: true, description: "Price (min 0)" },
          { name: "durationMonths", type: "integer", required: true, description: "Duration in months (min 1)" },
          { name: "chatbotAccess", type: "boolean", required: false, description: "Enable chatbot access" },
          { name: "accessibleCategoryIds", type: "integer[]", required: false, description: "Array of category IDs accessible with this package" },
          { name: "isActive", type: "boolean", required: false, description: "Whether package is active" },
        ],
        responseExample: `{ "data": { "id": 1, "name": "Premium", "price": 99.00, "durationMonths": 12, ... } }`,
      },
      {
        method: "GET",
        path: "/api/packages/{id}",
        auth: "Auth + Permission: packages.view",
        description: "Get a single package.",
        responseExample: `{ "data": { "id": 1, "name": "Premium", ... } }`,
      },
      {
        method: "PUT",
        path: "/api/packages/{id}",
        auth: "Auth + Permission: packages.edit",
        description: "Update a package.",
        params: [
          { name: "name", type: "string", required: false, description: "Package name" },
          { name: "description", type: "string", required: false, description: "Description" },
          { name: "price", type: "number", required: false, description: "Price (min 0)" },
          { name: "durationMonths", type: "integer", required: false, description: "Duration in months" },
          { name: "chatbotAccess", type: "boolean", required: false, description: "Enable chatbot access" },
          { name: "accessibleCategoryIds", type: "integer[]", required: false, description: "Array of accessible category IDs" },
          { name: "isActive", type: "boolean", required: false, description: "Whether package is active" },
        ],
        responseExample: `{ "data": { "id": 1, "name": "Updated", ... } }`,
      },
      {
        method: "DELETE",
        path: "/api/packages/{id}",
        auth: "Auth + Permission: packages.delete",
        description: "Delete a package. Fails if subscribers exist.",
        responseExample: `{ "data": { "success": true } }`,
      },
    ],
  },

  /* ── Topics / Categories ──────────────────────────────────────── */
  {
    id: "categories",
    title: "Topics / Categories",
    description: "Content categories used to organize notes, case summaries, questions, and other content types.",
    endpoints: [
      {
        method: "GET",
        path: "/api/categories",
        auth: "Auth + Permission: categories.view",
        description: "List categories. Supports filtering by type.",
        params: [...paginationParams, { name: "type", type: "string", required: false, description: "Filter by category type" }],
        responseExample: `{ "data": [...], "meta": { ... } }`,
      },
      {
        method: "POST",
        path: "/api/categories",
        auth: "Auth + Permission: categories.create",
        description: "Create a new category.",
        params: [
          { name: "name", type: "string", required: true, description: "Category name" },
          { name: "slug", type: "string", required: false, description: "URL slug (auto-generated if omitted)" },
          { name: "description", type: "string", required: false, description: "Description" },
          { name: "type", type: "string", required: false, description: "Category type" },
          { name: "iconUrl", type: "string", required: false, description: "Icon image URL" },
          { name: "sortOrder", type: "integer", required: false, description: "Sort position" },
        ],
        responseExample: `{ "data": { "id": 1, "name": "Contract Law", "slug": "contract-law", ... } }`,
      },
      {
        method: "GET",
        path: "/api/categories/{id}",
        auth: "Auth + Permission: categories.view",
        description: "Get a single category.",
        responseExample: `{ "data": { "id": 1, "name": "Contract Law", ... } }`,
      },
      {
        method: "PUT",
        path: "/api/categories/{id}",
        auth: "Auth + Permission: categories.edit",
        description: "Update a category.",
        params: [
          { name: "name", type: "string", required: false, description: "Category name" },
          { name: "slug", type: "string", required: false, description: "URL slug" },
          { name: "description", type: "string", required: false, description: "Description" },
          { name: "type", type: "string", required: false, description: "Category type" },
          { name: "iconUrl", type: "string", required: false, description: "Icon image URL" },
          { name: "sortOrder", type: "integer", required: false, description: "Sort position" },
        ],
        responseExample: `{ "data": { "id": 1, "name": "Updated", ... } }`,
      },
      {
        method: "DELETE",
        path: "/api/categories/{id}",
        auth: "Auth + Permission: categories.delete",
        description: "Delete a category.",
        responseExample: `{ "data": { "success": true } }`,
      },
    ],
  },

  /* ── Topic Links ──────────────────────────────────────────────── */
  {
    id: "topic-links",
    title: "Topic Links",
    description: "External links attached to a topic/category.",
    endpoints: [
      {
        method: "GET",
        path: "/api/topic-links",
        auth: "Auth + Permission: topic_links.view",
        description: "List topic links. Filter by categoryId.",
        params: [...paginationParams, { name: "categoryId", type: "integer", required: false, description: "Filter by category" }],
        responseExample: `{ "data": [...], "meta": { ... } }`,
      },
      {
        method: "POST",
        path: "/api/topic-links",
        auth: "Auth + Permission: topic_links.create",
        description: "Create a topic link.",
        params: [
          { name: "categoryId", type: "integer", required: true, description: "Parent category ID" },
          { name: "title", type: "string", required: true, description: "Link title" },
          { name: "url", type: "string", required: true, description: "Full URL" },
          { name: "sortOrder", type: "integer", required: false, description: "Sort position" },
          { name: "isActive", type: "boolean", required: false, description: "Whether link is active" },
        ],
        responseExample: `{ "data": { "id": 1, "title": "LawNet", "url": "https://...", ... } }`,
      },
      {
        method: "GET",
        path: "/api/topic-links/{id}",
        auth: "Auth + Permission: topic_links.view",
        description: "Get a single topic link.",
        responseExample: `{ "data": { "id": 1, "title": "LawNet", ... } }`,
      },
      {
        method: "PUT",
        path: "/api/topic-links/{id}",
        auth: "Auth + Permission: topic_links.edit",
        description: "Update a topic link.",
        params: [
          { name: "title", type: "string", required: false, description: "Link title" },
          { name: "url", type: "string", required: false, description: "Full URL" },
          { name: "sortOrder", type: "integer", required: false, description: "Sort position" },
          { name: "isActive", type: "boolean", required: false, description: "Whether link is active" },
        ],
        responseExample: `{ "data": { "id": 1, "title": "Updated", ... } }`,
      },
      {
        method: "DELETE",
        path: "/api/topic-links/{id}",
        auth: "Auth + Permission: topic_links.delete",
        description: "Delete a topic link.",
        responseExample: `{ "data": { "success": true } }`,
      },
    ],
  },

  /* ── Notes ────────────────────────────────────────────────────── */
  {
    id: "notes",
    title: "Notes",
    description: "File-based notes attached to categories. Create requires multipart form data (file upload).",
    endpoints: [
      {
        method: "GET",
        path: "/api/notes",
        auth: "Auth + Permission: notes.view",
        description: "List notes. Filter by categoryId.",
        params: [...paginationParams, { name: "categoryId", type: "integer", required: false, description: "Filter by category" }],
        responseExample: `{ "data": [...], "meta": { ... } }`,
      },
      {
        method: "POST",
        path: "/api/notes",
        auth: "Auth + Permission: notes.create",
        description: "Create a note with file upload. Use multipart/form-data.",
        params: [
          { name: "title", type: "string", required: true, description: "Note title" },
          { name: "description", type: "string", required: false, description: "Description" },
          { name: "categoryId", type: "integer", required: false, description: "Category ID" },
          { name: "file", type: "file", required: true, description: "File (pdf, jpeg, jpg, png; max 10 MB)" },
          { name: "sortOrder", type: "integer", required: false, description: "Sort position" },
          { name: "isPublished", type: "boolean", required: false, description: "Published state" },
        ],
        responseExample: `{ "data": { "id": 1, "title": "Note Title", "filePath": "...", ... } }`,
      },
      {
        method: "GET",
        path: "/api/notes/{id}",
        auth: "Auth + Permission: notes.view",
        description: "Get a single note with file info.",
        responseExample: `{ "data": { "id": 1, "title": "...", "filePath": "...", ... } }`,
      },
      {
        method: "PUT",
        path: "/api/notes/{id}",
        auth: "Auth + Permission: notes.edit",
        description: "Update note metadata (not the file).",
        params: [
          { name: "title", type: "string", required: false, description: "Note title" },
          { name: "description", type: "string", required: false, description: "Description" },
          { name: "categoryId", type: "integer", required: false, description: "Category ID" },
          { name: "sortOrder", type: "integer", required: false, description: "Sort position" },
          { name: "isPublished", type: "boolean", required: false, description: "Published state" },
        ],
        responseExample: `{ "data": { "id": 1, "title": "Updated", ... } }`,
      },
      {
        method: "DELETE",
        path: "/api/notes/{id}",
        auth: "Auth + Permission: notes.delete",
        description: "Delete a note and its associated file.",
        responseExample: `{ "data": { "success": true } }`,
      },
      {
        method: "POST",
        path: "/api/notes/{id}/upload",
        auth: "Auth + Permission: notes.create",
        description: "Replace the file for an existing note. Multipart. Rate-limited.",
        params: [
          { name: "file", type: "file", required: true, description: "Replacement file (pdf, jpeg, jpg, png; max 10 MB)" },
        ],
        responseExample: `{ "data": { "id": 1, "filePath": "/storage/notes/new-file.pdf", ... } }`,
      },
    ],
  },

  /* ── Case Summaries ───────────────────────────────────────────── */
  {
    id: "case-summaries",
    title: "Case Summaries",
    description: "Legal case summaries with optional PDF attachments.",
    endpoints: [
      {
        method: "GET",
        path: "/api/case-summaries",
        auth: "Auth + Permission: case_summaries.view",
        description: "List case summaries. Filter by categoryId.",
        params: [...paginationParams, { name: "categoryId", type: "integer", required: false, description: "Filter by category" }],
        responseExample: `{ "data": [...], "meta": { ... } }`,
      },
      {
        method: "POST",
        path: "/api/case-summaries",
        auth: "Auth + Permission: case_summaries.create",
        description: "Create a case summary. Supports optional PDF upload (multipart).",
        params: [
          { name: "title", type: "string", required: true, description: "Case title" },
          { name: "citation", type: "string", required: true, description: "Legal citation" },
          { name: "summaryText", type: "string", required: true, description: "Summary content" },
          { name: "categoryId", type: "integer", required: false, description: "Category ID" },
          { name: "pdfFile", type: "file", required: false, description: "PDF attachment (max 10 MB)" },
          { name: "isPublished", type: "boolean", required: false, description: "Published state" },
        ],
        responseExample: `{ "data": { "id": 1, "title": "...", "citation": "...", ... } }`,
      },
      {
        method: "GET",
        path: "/api/case-summaries/{id}",
        auth: "Auth + Permission: case_summaries.view",
        description: "Get a single case summary.",
        responseExample: `{ "data": { "id": 1, "title": "...", "citation": "...", "summaryText": "..." } }`,
      },
      {
        method: "PUT",
        path: "/api/case-summaries/{id}",
        auth: "Auth + Permission: case_summaries.edit",
        description: "Update a case summary.",
        params: [
          { name: "title", type: "string", required: false, description: "Case title" },
          { name: "citation", type: "string", required: false, description: "Legal citation" },
          { name: "summaryText", type: "string", required: false, description: "Summary content" },
          { name: "categoryId", type: "integer", required: false, description: "Category ID" },
          { name: "pdfFile", type: "file", required: false, description: "PDF attachment (max 10 MB)" },
          { name: "isPublished", type: "boolean", required: false, description: "Published state" },
        ],
        responseExample: `{ "data": { "id": 1, "title": "Updated", ... } }`,
      },
      {
        method: "DELETE",
        path: "/api/case-summaries/{id}",
        auth: "Auth + Permission: case_summaries.delete",
        description: "Delete a case summary.",
        responseExample: `{ "data": { "success": true } }`,
      },
    ],
  },

  /* ── Questions (MCQ) ──────────────────────────────────────────── */
  {
    id: "questions",
    title: "Questions (MCQ)",
    description: "Multiple-choice questions organized by category.",
    endpoints: [
      {
        method: "GET",
        path: "/api/questions",
        auth: "Auth + Permission: questions.view",
        description: "List MCQ questions. Filter by categoryId.",
        params: [...paginationParams, { name: "categoryId", type: "integer", required: false, description: "Filter by category" }],
        responseExample: `{ "data": [...], "meta": { ... } }`,
      },
      {
        method: "POST",
        path: "/api/questions",
        auth: "Auth + Permission: questions.create",
        description: "Create an MCQ question.",
        params: [
          { name: "categoryId", type: "integer", required: true, description: "Category ID" },
          { name: "questionText", type: "string", required: true, description: "The question text" },
          { name: "options", type: "string[]", required: true, description: "Exactly 4 option strings" },
          { name: "correctOptionIndex", type: "integer", required: true, description: "Index of correct answer (0-3)" },
          { name: "explanation", type: "string", required: false, description: "Explanation of the answer" },
          { name: "sortOrder", type: "integer", required: false, description: "Sort position" },
          { name: "isPublished", type: "boolean", required: false, description: "Published state" },
        ],
        responseExample: `{ "data": { "id": 1, "questionText": "...", "options": ["A","B","C","D"], "correctOptionIndex": 0 } }`,
      },
      {
        method: "GET",
        path: "/api/questions/{id}",
        auth: "Auth + Permission: questions.view",
        description: "Get a single question.",
        responseExample: `{ "data": { "id": 1, "questionText": "...", "options": [...], ... } }`,
      },
      {
        method: "PUT",
        path: "/api/questions/{id}",
        auth: "Auth + Permission: questions.edit",
        description: "Update a question.",
        params: [
          { name: "categoryId", type: "integer", required: false, description: "Category ID" },
          { name: "questionText", type: "string", required: false, description: "The question text" },
          { name: "options", type: "string[]", required: false, description: "Exactly 4 option strings" },
          { name: "correctOptionIndex", type: "integer", required: false, description: "Index of correct answer (0-3)" },
          { name: "explanation", type: "string", required: false, description: "Explanation of the answer" },
          { name: "sortOrder", type: "integer", required: false, description: "Sort position" },
          { name: "isPublished", type: "boolean", required: false, description: "Published state" },
        ],
        responseExample: `{ "data": { "id": 1, "questionText": "Updated", ... } }`,
      },
      {
        method: "DELETE",
        path: "/api/questions/{id}",
        auth: "Auth + Permission: questions.delete",
        description: "Delete a question.",
        responseExample: `{ "data": { "success": true } }`,
      },
    ],
  },

  /* ── Question Papers ──────────────────────────────────────────── */
  {
    id: "question-papers",
    title: "Question Papers",
    description: "Past year and topical question paper PDFs organized by category.",
    endpoints: [
      {
        method: "GET",
        path: "/api/question-papers",
        auth: "Auth + Permission: questions.view",
        description: "List question papers.",
        params: [...paginationParams],
        responseExample: `{ "data": [...], "meta": { ... } }`,
      },
      {
        method: "POST",
        path: "/api/question-papers",
        auth: "Auth + Permission: questions.create",
        description: "Create a question paper with PDF upload. Multipart.",
        params: [
          { name: "title", type: "string", required: true, description: "Paper title" },
          { name: "slug", type: "string", required: false, description: "URL slug (auto-generated)" },
          { name: "type", type: "string", required: true, description: "past_year | topical" },
          { name: "year", type: "integer", required: false, description: "Year of the paper" },
          { name: "categoryId", type: "integer", required: false, description: "Category ID" },
          { name: "description", type: "string", required: false, description: "Description" },
          { name: "file", type: "file", required: true, description: "PDF file (max 20 MB)" },
          { name: "sortOrder", type: "integer", required: false, description: "Sort position" },
          { name: "isPublished", type: "boolean", required: false, description: "Published state" },
        ],
        responseExample: `{ "data": { "id": 1, "title": "2024 Paper", "type": "past_year", ... } }`,
      },
      {
        method: "GET",
        path: "/api/question-papers/{id}",
        auth: "Auth + Permission: questions.view",
        description: "Get a single question paper.",
        responseExample: `{ "data": { "id": 1, "title": "...", "filePath": "...", ... } }`,
      },
      {
        method: "PUT",
        path: "/api/question-papers/{id}",
        auth: "Auth + Permission: questions.edit",
        description: "Update question paper metadata.",
        params: [
          { name: "title", type: "string", required: false, description: "Paper title" },
          { name: "slug", type: "string", required: false, description: "URL slug" },
          { name: "type", type: "string", required: false, description: "past_year | topical" },
          { name: "year", type: "integer", required: false, description: "Year" },
          { name: "categoryId", type: "integer", required: false, description: "Category ID" },
          { name: "description", type: "string", required: false, description: "Description" },
          { name: "sortOrder", type: "integer", required: false, description: "Sort position" },
          { name: "isPublished", type: "boolean", required: false, description: "Published state" },
        ],
        responseExample: `{ "data": { "id": 1, "title": "Updated", ... } }`,
      },
      {
        method: "DELETE",
        path: "/api/question-papers/{id}",
        auth: "Auth + Permission: questions.delete",
        description: "Delete a question paper and its file.",
        responseExample: `{ "data": { "success": true } }`,
      },
      {
        method: "POST",
        path: "/api/question-papers/{id}/upload",
        auth: "Auth + Permission: questions.create",
        description: "Replace the PDF file for an existing question paper. Multipart. Rate-limited.",
        params: [
          { name: "file", type: "file", required: true, description: "New PDF file (max 20 MB)" },
        ],
        responseExample: `{ "data": { "id": 1, "filePath": "/storage/...", ... } }`,
      },
    ],
  },

  /* ── Statutes ─────────────────────────────────────────────────── */
  {
    id: "statutes",
    title: "Statutes",
    description: "Statute references stored as external links or uploaded documents.",
    endpoints: [
      {
        method: "GET",
        path: "/api/statutes",
        auth: "Auth + Permission: statutes.view",
        description: "List statutes.",
        params: [...paginationParams],
        responseExample: `{ "data": [...], "meta": { ... } }`,
      },
      {
        method: "POST",
        path: "/api/statutes",
        auth: "Auth + Permission: statutes.create",
        description: "Create a statute. If type is 'document', use multipart for file upload.",
        params: [
          { name: "title", type: "string", required: true, description: "Statute title" },
          { name: "slug", type: "string", required: false, description: "URL slug (auto-generated)" },
          { name: "type", type: "string", required: true, description: "link | document" },
          { name: "url", type: "string", required: false, description: "URL (required if type = link)" },
          { name: "file", type: "file", required: false, description: "Document (pdf, doc, docx; max 20 MB; required if type = document)" },
          { name: "description", type: "string", required: false, description: "Description" },
          { name: "sortOrder", type: "integer", required: false, description: "Sort position" },
          { name: "isPublished", type: "boolean", required: false, description: "Published state" },
        ],
        responseExample: `{ "data": { "id": 1, "title": "Companies Act", "type": "link", "url": "...", ... } }`,
      },
      {
        method: "GET",
        path: "/api/statutes/{id}",
        auth: "Auth + Permission: statutes.view",
        description: "Get a single statute.",
        responseExample: `{ "data": { "id": 1, "title": "...", "type": "link", ... } }`,
      },
      {
        method: "PUT",
        path: "/api/statutes/{id}",
        auth: "Auth + Permission: statutes.edit",
        description: "Update statute metadata.",
        params: [
          { name: "title", type: "string", required: false, description: "Statute title" },
          { name: "slug", type: "string", required: false, description: "URL slug" },
          { name: "type", type: "string", required: false, description: "link | document" },
          { name: "url", type: "string", required: false, description: "URL" },
          { name: "file", type: "file", required: false, description: "Document file (pdf, doc, docx; max 20 MB)" },
          { name: "description", type: "string", required: false, description: "Description" },
          { name: "sortOrder", type: "integer", required: false, description: "Sort position" },
          { name: "isPublished", type: "boolean", required: false, description: "Published state" },
        ],
        responseExample: `{ "data": { "id": 1, "title": "Updated", ... } }`,
      },
      {
        method: "DELETE",
        path: "/api/statutes/{id}",
        auth: "Auth + Permission: statutes.delete",
        description: "Delete a statute and its file (if any).",
        responseExample: `{ "data": { "success": true } }`,
      },
      {
        method: "POST",
        path: "/api/statutes/{id}/upload",
        auth: "Auth + Permission: statutes.create",
        description: "Replace the document file for an existing statute. Multipart. Rate-limited.",
        params: [
          { name: "file", type: "file", required: true, description: "New document (pdf, doc, docx; max 20 MB)" },
        ],
        responseExample: `{ "data": { "id": 1, "filePath": "/storage/...", ... } }`,
      },
    ],
  },

  /* ── Free Links ───────────────────────────────────────────────── */
  {
    id: "free-links",
    title: "Free Links (Case Law)",
    description: "App-wide free directory links for case law resources.",
    endpoints: [
      {
        method: "GET",
        path: "/api/free-links",
        auth: "Auth + Permission: free_links.view",
        description: "List free links.",
        params: [...paginationParams],
        responseExample: `{ "data": [...], "meta": { ... } }`,
      },
      {
        method: "POST",
        path: "/api/free-links",
        auth: "Auth + Permission: free_links.create",
        description: "Create a free link.",
        params: [
          { name: "title", type: "string", required: true, description: "Link title" },
          { name: "url", type: "string", required: true, description: "Full URL" },
          { name: "iconImagePath", type: "string", required: false, description: "Icon image path" },
          { name: "sortOrder", type: "integer", required: false, description: "Sort position" },
          { name: "isActive", type: "boolean", required: false, description: "Whether link is active" },
        ],
        responseExample: `{ "data": { "id": 1, "title": "eLaw", "url": "https://...", ... } }`,
      },
      {
        method: "GET",
        path: "/api/free-links/{id}",
        auth: "Auth + Permission: free_links.view",
        description: "Get a single free link.",
        responseExample: `{ "data": { "id": 1, "title": "eLaw", ... } }`,
      },
      {
        method: "PUT",
        path: "/api/free-links/{id}",
        auth: "Auth + Permission: free_links.edit",
        description: "Update a free link.",
        params: [
          { name: "title", type: "string", required: false, description: "Link title" },
          { name: "url", type: "string", required: false, description: "Full URL" },
          { name: "iconImagePath", type: "string", required: false, description: "Icon image path" },
          { name: "sortOrder", type: "integer", required: false, description: "Sort position" },
          { name: "isActive", type: "boolean", required: false, description: "Whether link is active" },
        ],
        responseExample: `{ "data": { "id": 1, "title": "Updated", ... } }`,
      },
      {
        method: "DELETE",
        path: "/api/free-links/{id}",
        auth: "Auth + Permission: free_links.delete",
        description: "Delete a free link.",
        responseExample: `{ "data": { "success": true } }`,
      },
    ],
  },

  /* ── Media ────────────────────────────────────────────────────── */
  {
    id: "media",
    title: "Media",
    description: "Upload and manage media files (images) used across the CMS.",
    endpoints: [
      {
        method: "GET",
        path: "/api/media",
        auth: "Auth + Permission: media.view",
        description: "List all uploaded media files.",
        params: [...paginationParams],
        responseExample: `{ "data": [...], "meta": { ... } }`,
      },
      {
        method: "POST",
        path: "/api/media/upload",
        auth: "Auth + Permission: media.upload",
        description: "Upload a media file. Multipart form data. Rate-limited.",
        params: [
          { name: "file", type: "file", required: true, description: "Image file (png, jpg, jpeg, gif, webp, svg, ico; max 5 MB)" },
        ],
        responseExample: `{ "data": { "id": 1, "fileName": "image.png", "filePath": "/storage/media/...", "mimeType": "image/png" } }`,
      },
      {
        method: "PUT",
        path: "/api/media/{id}",
        auth: "Auth + Permission: media.upload",
        description: "Update media metadata.",
        params: [
          { name: "title", type: "string", required: false, description: "Title (max 255)" },
          { name: "altText", type: "string", required: false, description: "Alt text (max 255)" },
          { name: "caption", type: "string", required: false, description: "Caption" },
          { name: "description", type: "string", required: false, description: "Description" },
        ],
        responseExample: `{ "data": { "id": 1, "title": "Updated", "altText": "...", ... } }`,
      },
      {
        method: "DELETE",
        path: "/api/media/{id}",
        auth: "Auth + Permission: media.delete",
        description: "Delete a media file. Fails if media is referenced by other resources (409 MEDIA_IN_USE).",
        responseExample: `{ "data": { "success": true } }`,
      },
    ],
  },

  /* ── Dashboard ────────────────────────────────────────────────── */
  {
    id: "dashboard",
    title: "Dashboard",
    description: "Platform summary statistics.",
    endpoints: [
      {
        method: "GET",
        path: "/api/dashboard/summary",
        auth: "Auth + Permission: settings.view",
        description: "Get aggregate counts for all platform entities.",
        responseExample: `{ "data": { "totalMembers": 150, "totalCategories": 12, "totalNotes": 45, "totalQuestions": 200, ... } }`,
      },
    ],
  },

  /* ── Admin Users ──────────────────────────────────────────────── */
  {
    id: "users",
    title: "Admin Users",
    description: "Manage admin panel user accounts.",
    endpoints: [
      {
        method: "GET",
        path: "/api/users",
        auth: "Auth + Permission: users.view",
        description: "List admin panel users.",
        params: [...paginationParams],
        responseExample: `{ "data": [...], "meta": { ... } }`,
      },
      {
        method: "POST",
        path: "/api/users",
        auth: "Auth + Permission: users.create",
        description: "Create an admin panel user.",
        params: [
          { name: "name", type: "string", required: true, description: "Full name" },
          { name: "email", type: "string", required: true, description: "Email (unique)" },
          { name: "password", type: "string", required: true, description: "Password (min 6)" },
          { name: "role", type: "string", required: false, description: "Role name" },
          { name: "isActive", type: "boolean", required: false, description: "Account active state" },
        ],
        responseExample: `{ "data": { "id": 2, "name": "Editor", "email": "editor@example.com", "role": "editor" } }`,
      },
      {
        method: "GET",
        path: "/api/users/{id}",
        auth: "Auth + Permission: users.view",
        description: "Get a single admin user.",
        responseExample: `{ "data": { "id": 2, "name": "Editor", ... } }`,
      },
      {
        method: "PUT",
        path: "/api/users/{id}",
        auth: "Auth + Permission: users.edit",
        description: "Update an admin user.",
        params: [
          { name: "name", type: "string", required: false, description: "Full name" },
          { name: "email", type: "string", required: false, description: "Email (unique)" },
          { name: "password", type: "string", required: false, description: "New password (min 6)" },
          { name: "role", type: "string", required: false, description: "Role name" },
          { name: "isActive", type: "boolean", required: false, description: "Account active state" },
        ],
        responseExample: `{ "data": { "id": 2, "name": "Updated", ... } }`,
      },
      {
        method: "DELETE",
        path: "/api/users/{id}",
        auth: "Auth + Permission: users.delete",
        description: "Delete an admin user.",
        responseExample: `{ "data": { "success": true } }`,
      },
      {
        method: "PUT",
        path: "/api/users/{id}/status",
        auth: "Auth + Permission: users.edit",
        description: "Set an admin user's account status.",
        params: [
          { name: "status", type: "string", required: true, description: "active | suspended" },
        ],
        responseExample: `{ "data": { "id": 2, "status": "active", ... } }`,
      },
    ],
  },

  /* ── Roles & Permissions ──────────────────────────────────────── */
  {
    id: "roles",
    title: "Roles & Permissions",
    description: "Manage RBAC roles and list available permissions.",
    endpoints: [
      {
        method: "GET",
        path: "/api/roles",
        auth: "Auth + Permission: roles.view",
        description: "List all roles with their permissions.",
        params: [...paginationParams],
        responseExample: `{ "data": [{ "id": 1, "name": "admin", "permissions": [...] }], "meta": { ... } }`,
      },
      {
        method: "POST",
        path: "/api/roles",
        auth: "Auth + Permission: roles.create",
        description: "Create a new role.",
        params: [
          { name: "name", type: "string", required: true, description: "Role name (unique)" },
          { name: "description", type: "string", required: false, description: "Description" },
          { name: "permissions", type: "string[]", required: false, description: "Array of permission strings" },
        ],
        responseExample: `{ "data": { "id": 3, "name": "viewer", "permissions": ["posts.view"] } }`,
      },
      {
        method: "GET",
        path: "/api/roles/{id}",
        auth: "Auth + Permission: roles.view",
        description: "Get a single role with permissions.",
        responseExample: `{ "data": { "id": 1, "name": "admin", "permissions": [...] } }`,
      },
      {
        method: "PUT",
        path: "/api/roles/{id}",
        auth: "Auth + Permission: roles.edit",
        description: "Update a role's name, description, or permissions.",
        params: [
          { name: "name", type: "string", required: false, description: "Role name (unique)" },
          { name: "description", type: "string", required: false, description: "Description" },
          { name: "permissions", type: "string[]", required: false, description: "Array of permission strings" },
        ],
        responseExample: `{ "data": { "id": 1, "name": "admin", "permissions": [...] } }`,
      },
      {
        method: "DELETE",
        path: "/api/roles/{id}",
        auth: "Auth + Permission: roles.delete",
        description: "Delete a role.",
        responseExample: `{ "data": { "success": true } }`,
      },
      {
        method: "GET",
        path: "/api/permissions",
        auth: "Auth + Permission: roles.view",
        description: "List all available permission strings from the Permission class.",
        responseExample: `{ "data": ["posts.view", "posts.create", "posts.edit", "posts.delete", ...] }`,
      },
    ],
  },

  /* ── Audit Logs ───────────────────────────────────────────────── */
  {
    id: "audit-logs",
    title: "Audit Logs",
    description: "Read-only access to the platform audit trail.",
    endpoints: [
      {
        method: "GET",
        path: "/api/audit-logs",
        auth: "Auth + Permission: audit.read",
        description: "List audit log entries with pagination.",
        params: [...paginationParams],
        responseExample: `{ "data": [{ "id": 1, "userId": 1, "action": "created", "entityType": "Post", ... }], "meta": { ... } }`,
      },
    ],
  },
];

/* ------------------------------------------------------------------ */
/*  Computed: filtered groups                                          */
/* ------------------------------------------------------------------ */

const totalEndpoints = computed(() => groups.reduce((sum, g) => sum + g.endpoints.length, 0));

const filteredGroups = computed(() => {
  const q = searchQuery.value.toLowerCase().trim();
  const methodF = activeMethodFilter.value;

  return groups
    .map((group) => {
      const filtered = group.endpoints.filter((ep) => {
        const matchMethod = methodF === "ALL" || ep.method === methodF;
        const matchSearch =
          !q ||
          ep.path.toLowerCase().includes(q) ||
          ep.description.toLowerCase().includes(q) ||
          ep.auth.toLowerCase().includes(q) ||
          group.title.toLowerCase().includes(q);
        return matchMethod && matchSearch;
      });
      return { ...group, endpoints: filtered };
    })
    .filter((g) => g.endpoints.length > 0);
});

const filteredEndpointCount = computed(() =>
  filteredGroups.value.reduce((sum, g) => sum + g.endpoints.length, 0),
);

function authBadgeClass(auth: string) {
  if (auth === "Public") return "bg-emerald-50 text-emerald-700 ring-emerald-200";
  if (auth === "Auth Required") return "bg-amber-50 text-amber-700 ring-amber-200";
  return "bg-violet-50 text-violet-700 ring-violet-200";
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-6xl space-y-6 pb-12">
      <!-- ── Hero ──────────────────────────────────────────────── -->
      <div class="admin-hero">
        <div class="space-y-2">
          <p class="page-kicker">Developer Operations</p>
          <h1 class="page-title">API Reference</h1>
          <p class="page-subtitle">
            Comprehensive reference for mobile-app integration, internal testing, and backend handoff.
            Covers {{ totalEndpoints }} endpoints across {{ groups.length }} modules.
          </p>
        </div>
      </div>

      <!-- ── Header info banners ───────────────────────────────── -->
      <div class="space-y-3">
        <!-- Base URL & Authentication -->
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
          <div class="space-y-2">
            <p>
              <strong>Base URL:</strong>
              <code class="rounded bg-blue-100 px-1.5 py-0.5 text-xs">API_BASE_URL</code> from environment
              (e.g. <code class="rounded bg-blue-100 px-1.5 py-0.5 text-xs">http://localhost:8000</code>)
            </p>
            <p>
              <strong>SPA Auth:</strong> Session-based via Sanctum.
              Call <code class="rounded bg-blue-100 px-1.5 py-0.5 text-xs">POST /api/auth/login</code>,
              then include <code class="rounded bg-blue-100 px-1.5 py-0.5 text-xs">credentials: 'include'</code>
              and send the <code class="rounded bg-blue-100 px-1.5 py-0.5 text-xs">X-XSRF-TOKEN</code> header
              (from <code class="rounded bg-blue-100 px-1.5 py-0.5 text-xs">XSRF-TOKEN</code> cookie) on all state-mutating requests.
            </p>
            <p>
              <strong>Mobile Auth:</strong> Token-based via Sanctum.
              Call <code class="rounded bg-blue-100 px-1.5 py-0.5 text-xs">POST /api/mobile/login</code>
              to receive a Bearer token, then send
              <code class="rounded bg-blue-100 px-1.5 py-0.5 text-xs">Authorization: Bearer &lt;token&gt;</code> on all subsequent requests.
            </p>
          </div>
        </div>

        <!-- Response Envelope -->
        <div class="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-700">
          <div class="space-y-2">
            <p>
              <strong class="text-slate-900">Response Envelope:</strong>
              All responses follow a standard envelope format.
            </p>
            <div class="grid gap-2 sm:grid-cols-3">
              <div class="rounded border border-slate-100 bg-slate-50 p-2">
                <p class="mb-1 text-xs font-semibold text-slate-500">Success (single)</p>
                <code class="text-xs">{ "data": { ... } }</code>
              </div>
              <div class="rounded border border-slate-100 bg-slate-50 p-2">
                <p class="mb-1 text-xs font-semibold text-slate-500">Success (list)</p>
                <code class="text-xs">{ "data": [...], "meta": { page, limit, total, totalPages } }</code>
              </div>
              <div class="rounded border border-slate-100 bg-slate-50 p-2">
                <p class="mb-1 text-xs font-semibold text-slate-500">Error</p>
                <code class="text-xs">{ "error": { "code": "...", "message": "..." } }</code>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination & CamelCase -->
        <div class="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-700">
          <div class="flex flex-col gap-2 sm:flex-row sm:gap-6">
            <div>
              <strong class="text-slate-900">Pagination:</strong>
              <code class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-xs">?page=1&amp;limit=10&amp;q=search&amp;sortBy=createdAt&amp;sortDir=desc</code>
            </div>
            <div>
              <strong class="text-slate-900">Key Format:</strong>
              Requests accept <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">camelCase</code>,
              responses return <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">camelCase</code>
              (auto-converted by middleware).
            </div>
          </div>
        </div>
      </div>

      <!-- ── Search & Filters ──────────────────────────────────── -->
      <div class="sticky top-0 z-10 rounded-lg border border-slate-200 bg-white/95 p-3 shadow-sm backdrop-blur">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
          <!-- Search -->
          <div class="relative flex-1">
            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search endpoints by path, description, or permission..."
              class="w-full rounded-md border border-slate-200 bg-slate-50 py-2 pl-9 pr-8 text-sm text-slate-700 placeholder-slate-400 outline-none focus:border-slate-300 focus:bg-white focus:ring-1 focus:ring-slate-300"
            />
            <button
              v-if="searchQuery"
              class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-0.5 text-slate-400 hover:text-slate-600"
              @click="searchQuery = ''"
            >
              <X class="h-3.5 w-3.5" />
            </button>
          </div>

          <!-- Method filters -->
          <div class="flex items-center gap-1.5">
            <button
              v-for="f in methodFilters"
              :key="f.value"
              class="rounded-md px-2.5 py-1.5 text-xs font-semibold transition-all"
              :class="[
                f.cls,
                activeMethodFilter === f.value
                  ? 'ring-2 ring-offset-1 ring-slate-400 shadow-sm'
                  : 'opacity-60 hover:opacity-100',
              ]"
              @click="activeMethodFilter = f.value"
            >
              {{ f.label }}
            </button>
          </div>

          <!-- Count -->
          <span class="whitespace-nowrap text-xs text-slate-400">
            {{ filteredEndpointCount }} / {{ totalEndpoints }} endpoints
          </span>
        </div>
      </div>

      <!-- ── Endpoint Groups ───────────────────────────────────── -->
      <div class="space-y-4">
        <details
          v-for="group in filteredGroups"
          :key="group.id"
          class="group rounded-lg border border-slate-200 bg-white shadow-sm"
          open
        >
          <summary
            class="flex cursor-pointer select-none items-center gap-3 px-5 py-4 hover:bg-slate-50"
          >
            <div
              class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 transition-colors group-open:bg-slate-200"
            >
              <BookOpen class="h-4 w-4 text-slate-600" />
            </div>
            <div class="flex-1">
              <h2 class="text-sm font-semibold text-slate-900">{{ group.title }}</h2>
              <p class="text-xs text-slate-500">{{ group.description }}</p>
            </div>
            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">
              {{ group.endpoints.length }}
            </span>
          </summary>

          <div class="divide-y divide-slate-100 border-t border-slate-100">
            <details
              v-for="(ep, idx) in group.endpoints"
              :key="idx"
              class="group/ep"
            >
              <summary
                class="flex cursor-pointer items-start gap-3 px-5 py-3 hover:bg-slate-50/70"
              >
                <!-- Method badge -->
                <span
                  class="mt-0.5 inline-flex w-[4.5rem] shrink-0 items-center justify-center rounded px-2 py-0.5 text-[11px] font-bold font-mono ring-1"
                  :class="methodBadge[ep.method]"
                >
                  {{ ep.method }}
                </span>

                <!-- Path + description -->
                <div class="min-w-0 flex-1">
                  <code class="text-xs font-semibold text-slate-800">{{ ep.path }}</code>
                  <p class="mt-0.5 text-xs text-slate-500">{{ ep.description }}</p>
                </div>

                <!-- Auth badge -->
                <span
                  class="mt-0.5 shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium ring-1"
                  :class="authBadgeClass(ep.auth)"
                >
                  {{ ep.auth }}
                </span>
              </summary>

              <!-- Expanded detail -->
              <div class="space-y-3 border-t border-slate-50 bg-slate-50/40 px-5 py-4">
                <!-- Note -->
                <p v-if="ep.note" class="text-xs italic text-slate-500">
                  {{ ep.note }}
                </p>

                <!-- Parameters table -->
                <div v-if="ep.params && ep.params.length">
                  <h4 class="mb-1.5 text-xs font-semibold text-slate-600">Request Parameters</h4>
                  <div class="overflow-x-auto rounded border border-slate-200 bg-white">
                    <table class="w-full text-xs">
                      <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80">
                          <th class="px-3 py-1.5 text-left font-semibold text-slate-500">Name</th>
                          <th class="px-3 py-1.5 text-left font-semibold text-slate-500">Type</th>
                          <th class="px-3 py-1.5 text-center font-semibold text-slate-500">Required</th>
                          <th class="px-3 py-1.5 text-left font-semibold text-slate-500">Description</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-slate-50">
                        <tr v-for="p in ep.params" :key="p.name" class="hover:bg-slate-50/50">
                          <td class="px-3 py-1.5 font-mono text-slate-700">{{ p.name }}</td>
                          <td class="px-3 py-1.5 text-slate-500">{{ p.type }}</td>
                          <td class="px-3 py-1.5 text-center">
                            <span
                              v-if="p.required"
                              class="inline-block rounded bg-rose-50 px-1.5 py-0.5 text-[10px] font-semibold text-rose-600"
                            >
                              Required
                            </span>
                            <span
                              v-else
                              class="inline-block rounded bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-400"
                            >
                              Optional
                            </span>
                          </td>
                          <td class="px-3 py-1.5 text-slate-600">{{ p.description }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Response example -->
                <div v-if="ep.responseExample">
                  <h4 class="mb-1.5 text-xs font-semibold text-slate-600">Response Example</h4>
                  <pre
                    class="overflow-x-auto rounded border border-slate-200 bg-slate-900 p-3 text-[11px] leading-relaxed text-emerald-300"
                  ><code>{{ ep.responseExample }}</code></pre>
                </div>
              </div>
            </details>
          </div>
        </details>
      </div>

      <!-- ── Error codes reference ─────────────────────────────── -->
      <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-2.5 border-b border-slate-100 px-5 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Standard Error Codes</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-xs">
            <thead>
              <tr class="border-b border-slate-100 bg-slate-50/80">
                <th class="px-5 py-2 text-left font-semibold text-slate-500">HTTP</th>
                <th class="px-5 py-2 text-left font-semibold text-slate-500">Code</th>
                <th class="px-5 py-2 text-left font-semibold text-slate-500">Usage</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr>
                <td class="px-5 py-2 font-mono text-slate-700">400</td>
                <td class="px-5 py-2"><code class="rounded bg-slate-100 px-1 text-slate-700">BAD_REQUEST</code></td>
                <td class="px-5 py-2 text-slate-600">Malformed request</td>
              </tr>
              <tr>
                <td class="px-5 py-2 font-mono text-slate-700">401</td>
                <td class="px-5 py-2"><code class="rounded bg-slate-100 px-1 text-slate-700">UNAUTHORIZED</code></td>
                <td class="px-5 py-2 text-slate-600">Not authenticated</td>
              </tr>
              <tr>
                <td class="px-5 py-2 font-mono text-slate-700">403</td>
                <td class="px-5 py-2"><code class="rounded bg-slate-100 px-1 text-slate-700">FORBIDDEN</code></td>
                <td class="px-5 py-2 text-slate-600">Missing required permission</td>
              </tr>
              <tr>
                <td class="px-5 py-2 font-mono text-slate-700">404</td>
                <td class="px-5 py-2"><code class="rounded bg-slate-100 px-1 text-slate-700">NOT_FOUND</code></td>
                <td class="px-5 py-2 text-slate-600">Resource not found</td>
              </tr>
              <tr>
                <td class="px-5 py-2 font-mono text-slate-700">409</td>
                <td class="px-5 py-2"><code class="rounded bg-slate-100 px-1 text-slate-700">MEDIA_IN_USE</code></td>
                <td class="px-5 py-2 text-slate-600">Cannot delete media referenced by other resources</td>
              </tr>
              <tr>
                <td class="px-5 py-2 font-mono text-slate-700">422</td>
                <td class="px-5 py-2"><code class="rounded bg-slate-100 px-1 text-slate-700">VALIDATION_ERROR</code></td>
                <td class="px-5 py-2 text-slate-600">Validation failed (details contain field errors)</td>
              </tr>
              <tr>
                <td class="px-5 py-2 font-mono text-slate-700">429</td>
                <td class="px-5 py-2"><code class="rounded bg-slate-100 px-1 text-slate-700">TOO_MANY_REQUESTS</code></td>
                <td class="px-5 py-2 text-slate-600">Rate limit exceeded</td>
              </tr>
              <tr>
                <td class="px-5 py-2 font-mono text-slate-700">500</td>
                <td class="px-5 py-2"><code class="rounded bg-slate-100 px-1 text-slate-700">INTERNAL_ERROR</code></td>
                <td class="px-5 py-2 text-slate-600">Unexpected server error</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
