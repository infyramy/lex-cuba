<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import { useAuthStore } from "@/stores/auth";
import {
  devInfo,
  devTables,
  devTableRows,
  devCreateRow,
  devUpdateRow,
  devDeleteRow,
} from "@/api/cms";
import { apiRequest } from "@/api/client";
import { API_BASE_URL } from "@/env";
import { useToast } from "@/composables/useToast";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import {
  AlertTriangle,
  RefreshCw,
  Copy,
  Check,
  Send,
  Plus,
  Pencil,
  Trash2,
  ChevronLeft,
  ChevronRight,
  X,
  Database,
  Shield,
  Zap,
  Info,
} from "lucide-vue-next";

const toast = useToast();
const { confirm } = useConfirmDialog();
const auth = useAuthStore();

// ── Active Tab ─────────────────────────────────────────────────────────────
const activeTab = ref<"sysinfo" | "auth" | "api" | "db">("sysinfo");

const tabs = [
  { id: "sysinfo", label: "System Info",    icon: Info },
  { id: "auth",    label: "Auth & Session", icon: Shield },
  { id: "api",     label: "API Tester",     icon: Zap },
  { id: "db",      label: "DB Browser",     icon: Database },
] as const;

// ──────────────────────────────────────────────────────────────────────────────
// TAB 1 — System Info
// ──────────────────────────────────────────────────────────────────────────────
const sysInfo = ref<Record<string, unknown> | null>(null);
const sysLoading = ref(false);

async function loadSysInfo() {
  sysLoading.value = true;
  try {
    const res = await devInfo();
    sysInfo.value = res.data;
  } catch (e: unknown) {
    toast.error("Failed to load system info");
  } finally {
    sysLoading.value = false;
  }
}

const dbInfo = computed(() => {
  if (!sysInfo.value) return null;
  return sysInfo.value.database as Record<string, unknown>;
});

const envColor = computed(() => {
  const env = sysInfo.value?.app_env as string;
  if (env === "production") return "bg-red-600 text-white";
  if (env === "staging") return "bg-amber-500 text-white";
  return "bg-green-600 text-white";
});

const bannerColor = computed(() => {
  const env = sysInfo.value?.app_env as string;
  return env === "production"
    ? "bg-red-600 border-red-700"
    : "bg-amber-500 border-amber-600";
});

// ──────────────────────────────────────────────────────────────────────────────
// TAB 2 — Auth & Session
// ──────────────────────────────────────────────────────────────────────────────
const authUser = computed(() => auth.user);
const liveUser = ref<Record<string, unknown> | null>(null);
const authLoading = ref(false);
const copiedAuth = ref(false);

async function loadLiveUser() {
  authLoading.value = true;
  try {
    const res = await apiRequest<{ data: { user: Record<string, unknown> } }>("/api/auth/me");
    liveUser.value = res.data.user;
  } catch {
    toast.error("Failed to fetch live user");
  } finally {
    authLoading.value = false;
  }
}

function getXsrfToken(): string {
  const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
  return match ? decodeURIComponent(match[1]) : "(not found)";
}

function maskToken(token: string): string {
  if (token.length <= 8) return "****";
  return token.slice(0, 6) + "..." + token.slice(-4);
}

async function copyAuthJson() {
  const payload = JSON.stringify(liveUser.value ?? authUser.value, null, 2);
  await navigator.clipboard.writeText(payload);
  copiedAuth.value = true;
  setTimeout(() => (copiedAuth.value = false), 2000);
}

// ──────────────────────────────────────────────────────────────────────────────
// TAB 3 — API Tester
// ──────────────────────────────────────────────────────────────────────────────
type EndpointDef = {
  group: string;
  method: "GET" | "POST" | "PUT" | "DELETE" | "PATCH";
  path: string;
  body: Record<string, unknown> | null;
  description?: string;
};

const ENDPOINTS: EndpointDef[] = [
  // Auth
  { group: "Auth", method: "GET",    path: "/api/auth/me",                    body: null, description: "Get current user" },
  { group: "Auth", method: "PUT",    path: "/api/auth/me",                    body: { name: "", email: "" }, description: "Update profile" },
  { group: "Auth", method: "POST",   path: "/api/auth/password",              body: { current_password: "", new_password: "", new_password_confirmation: "" }, description: "Change password" },
  // Dashboard
  { group: "Dashboard", method: "GET", path: "/api/dashboard/summary",        body: null, description: "Dashboard summary stats" },
  // Settings
  { group: "Settings", method: "GET",  path: "/api/settings",                 body: null, description: "Get all settings (public)" },
  { group: "Settings", method: "PUT",  path: "/api/settings",                 body: { site_name: "", site_description: "" }, description: "Update settings" },
  // Users
  { group: "Users", method: "GET",    path: "/api/users",                     body: null, description: "List admin users" },
  { group: "Users", method: "POST",   path: "/api/users",                     body: { name: "", email: "", password: "", role: "admin" }, description: "Create admin user" },
  { group: "Users", method: "GET",    path: "/api/users/{id}",                body: null, description: "Get user by ID" },
  { group: "Users", method: "PUT",    path: "/api/users/{id}",                body: { name: "", email: "" }, description: "Update user" },
  { group: "Users", method: "DELETE", path: "/api/users/{id}",                body: null, description: "Delete user" },
  { group: "Users", method: "PUT",    path: "/api/users/{id}/status",         body: { is_active: true }, description: "Update user status" },
  // Members
  { group: "Members", method: "GET",    path: "/api/members",                 body: null, description: "List members" },
  { group: "Members", method: "POST",   path: "/api/members",                 body: { name: "", email: "", password: "" }, description: "Create member" },
  { group: "Members", method: "GET",    path: "/api/members/{id}",            body: null, description: "Get member" },
  { group: "Members", method: "PUT",    path: "/api/members/{id}",            body: { name: "" }, description: "Update member" },
  { group: "Members", method: "DELETE", path: "/api/members/{id}",            body: null, description: "Delete member" },
  { group: "Members", method: "PUT",    path: "/api/members/{id}/status",     body: { status: "active" }, description: "Update member status" },
  // Subscriptions
  { group: "Subscriptions", method: "GET",    path: "/api/members/{id}/subscription", body: null, description: "Get member subscription" },
  { group: "Subscriptions", method: "POST",   path: "/api/members/{id}/subscription", body: { package_id: 1, subscribed_at: "", expires_at: "" }, description: "Assign subscription" },
  { group: "Subscriptions", method: "DELETE", path: "/api/members/{id}/subscription", body: null, description: "Remove subscription" },
  // Packages
  { group: "Packages", method: "GET",    path: "/api/packages",              body: null, description: "List packages" },
  { group: "Packages", method: "POST",   path: "/api/packages",              body: { name: "", price: 0, duration_months: 1 }, description: "Create package" },
  { group: "Packages", method: "GET",    path: "/api/packages/{id}",         body: null, description: "Get package" },
  { group: "Packages", method: "PUT",    path: "/api/packages/{id}",         body: { name: "", price: 0 }, description: "Update package" },
  { group: "Packages", method: "DELETE", path: "/api/packages/{id}",         body: null, description: "Delete package" },
  // Categories/Topics
  { group: "Topics", method: "GET",    path: "/api/categories",              body: null, description: "List topics/categories" },
  { group: "Topics", method: "POST",   path: "/api/categories",              body: { name: "", type: "notes" }, description: "Create topic" },
  { group: "Topics", method: "GET",    path: "/api/categories/{id}",         body: null, description: "Get topic" },
  { group: "Topics", method: "PUT",    path: "/api/categories/{id}",         body: { name: "" }, description: "Update topic" },
  { group: "Topics", method: "DELETE", path: "/api/categories/{id}",         body: null, description: "Delete topic" },
  // Topic Links
  { group: "Topic Links", method: "GET",    path: "/api/topic-links",        body: null, description: "List topic links" },
  { group: "Topic Links", method: "POST",   path: "/api/topic-links",        body: { category_id: 1, title: "", url: "" }, description: "Create topic link" },
  { group: "Topic Links", method: "GET",    path: "/api/topic-links/{id}",   body: null, description: "Get topic link" },
  { group: "Topic Links", method: "PUT",    path: "/api/topic-links/{id}",   body: { title: "", url: "" }, description: "Update topic link" },
  { group: "Topic Links", method: "DELETE", path: "/api/topic-links/{id}",   body: null, description: "Delete topic link" },
  // Notes
  { group: "Notes", method: "GET",    path: "/api/notes",                    body: null, description: "List notes" },
  { group: "Notes", method: "GET",    path: "/api/notes/{id}",               body: null, description: "Get note" },
  { group: "Notes", method: "PUT",    path: "/api/notes/{id}",               body: { title: "", is_published: true }, description: "Update note" },
  { group: "Notes", method: "DELETE", path: "/api/notes/{id}",               body: null, description: "Delete note" },
  // Case Summaries
  { group: "Case Summaries", method: "GET",    path: "/api/case-summaries",  body: null, description: "List case summaries" },
  { group: "Case Summaries", method: "POST",   path: "/api/case-summaries",  body: { title: "", citation: "", summary_text: "", category_id: 1 }, description: "Create case summary" },
  { group: "Case Summaries", method: "GET",    path: "/api/case-summaries/{id}", body: null, description: "Get case summary" },
  { group: "Case Summaries", method: "PUT",    path: "/api/case-summaries/{id}", body: { title: "" }, description: "Update case summary" },
  { group: "Case Summaries", method: "DELETE", path: "/api/case-summaries/{id}", body: null, description: "Delete case summary" },
  // Questions
  { group: "Questions", method: "GET",    path: "/api/questions",            body: null, description: "List questions" },
  { group: "Questions", method: "POST",   path: "/api/questions",            body: { category_id: 1, question_text: "", options: ["A","B","C","D"], correct_option_index: 0 }, description: "Create question" },
  { group: "Questions", method: "GET",    path: "/api/questions/{id}",       body: null, description: "Get question" },
  { group: "Questions", method: "PUT",    path: "/api/questions/{id}",       body: { question_text: "" }, description: "Update question" },
  { group: "Questions", method: "DELETE", path: "/api/questions/{id}",       body: null, description: "Delete question" },
  // Question Papers
  { group: "Question Papers", method: "GET",    path: "/api/question-papers", body: null, description: "List question papers" },
  { group: "Question Papers", method: "GET",    path: "/api/question-papers/{id}", body: null, description: "Get question paper" },
  { group: "Question Papers", method: "PUT",    path: "/api/question-papers/{id}", body: { title: "" }, description: "Update question paper" },
  { group: "Question Papers", method: "DELETE", path: "/api/question-papers/{id}", body: null, description: "Delete question paper" },
  // Statutes
  { group: "Statutes", method: "GET",    path: "/api/statutes",              body: null, description: "List statutes" },
  { group: "Statutes", method: "GET",    path: "/api/statutes/{id}",         body: null, description: "Get statute" },
  { group: "Statutes", method: "PUT",    path: "/api/statutes/{id}",         body: { title: "" }, description: "Update statute" },
  { group: "Statutes", method: "DELETE", path: "/api/statutes/{id}",         body: null, description: "Delete statute" },
  // Free Links
  { group: "Free Links", method: "GET",    path: "/api/free-links",          body: null, description: "List case law links" },
  { group: "Free Links", method: "POST",   path: "/api/free-links",          body: { title: "", url: "" }, description: "Create free link" },
  { group: "Free Links", method: "GET",    path: "/api/free-links/{id}",     body: null, description: "Get free link" },
  { group: "Free Links", method: "PUT",    path: "/api/free-links/{id}",     body: { title: "", url: "" }, description: "Update free link" },
  { group: "Free Links", method: "DELETE", path: "/api/free-links/{id}",     body: null, description: "Delete free link" },
  // Media
  { group: "Media", method: "GET",    path: "/api/media",                    body: null, description: "List media files" },
  { group: "Media", method: "PUT",    path: "/api/media/{id}",               body: { title: "", alt_text: "" }, description: "Update media metadata" },
  { group: "Media", method: "DELETE", path: "/api/media/{id}",               body: null, description: "Delete media file" },
  // Roles
  { group: "Roles", method: "GET",    path: "/api/roles",                    body: null, description: "List roles" },
  { group: "Roles", method: "POST",   path: "/api/roles",                    body: { name: "", description: "", permissions: [] }, description: "Create role" },
  { group: "Roles", method: "GET",    path: "/api/roles/{id}",               body: null, description: "Get role" },
  { group: "Roles", method: "PUT",    path: "/api/roles/{id}",               body: { name: "", permissions: [] }, description: "Update role" },
  { group: "Roles", method: "DELETE", path: "/api/roles/{id}",               body: null, description: "Delete role" },
  { group: "Roles", method: "GET",    path: "/api/permissions",              body: null, description: "List all permissions" },
  // Audit Logs
  { group: "Audit Logs", method: "GET", path: "/api/audit-logs",             body: null, description: "List audit logs" },
  // Dev Console
  { group: "Dev Console", method: "GET",    path: "/api/dev/info",           body: null, description: "System info" },
  { group: "Dev Console", method: "GET",    path: "/api/dev/tables",         body: null, description: "List DB tables" },
  { group: "Dev Console", method: "GET",    path: "/api/dev/tables/{table}", body: null, description: "Browse table rows" },
  // Mobile Auth
  { group: "Mobile", method: "POST", path: "/api/mobile/login",              body: { email: "", password: "" }, description: "Mobile login" },
  { group: "Mobile", method: "GET",  path: "/api/mobile/me",                 body: null, description: "Mobile current user" },
  { group: "Mobile", method: "GET",  path: "/api/mobile/categories",         body: null, description: "Mobile categories" },
];

const apiSearch = ref("");
const selectedEndpoint = ref<EndpointDef | null>(null);
const pathParamValues = ref<Record<string, string>>({});
const queryParams = ref<{ key: string; value: string }[]>([]);
const requestBodyText = ref("");
const requestMethod = ref("GET");
const apiResponse = ref<{
  status: number;
  ms: number;
  body: unknown;
  ok: boolean;
} | null>(null);
const apiLoading = ref(false);
const copiedResponse = ref(false);

const filteredGroups = computed(() => {
  const q = apiSearch.value.toLowerCase();
  const groups: Record<string, EndpointDef[]> = {};
  for (const ep of ENDPOINTS) {
    if (q && !ep.path.toLowerCase().includes(q) && !ep.group.toLowerCase().includes(q) && !ep.description?.toLowerCase().includes(q)) continue;
    if (!groups[ep.group]) groups[ep.group] = [];
    groups[ep.group].push(ep);
  }
  return groups;
});

function extractPathParams(path: string): string[] {
  const matches = path.match(/\{([^}]+)\}/g);
  return matches ? matches.map((m) => m.slice(1, -1)) : [];
}

function selectEndpoint(ep: EndpointDef) {
  selectedEndpoint.value = ep;
  requestMethod.value = ep.method;
  pathParamValues.value = {};
  const params = extractPathParams(ep.path);
  params.forEach((p) => (pathParamValues.value[p] = ""));
  queryParams.value = ep.method === "GET" && ep.path.includes("/api/") ? [{ key: "", value: "" }] : [];
  requestBodyText.value = ep.body ? JSON.stringify(ep.body, null, 2) : "";
  apiResponse.value = null;
}

function resolvedPath(): string {
  if (!selectedEndpoint.value) return "";
  let path = selectedEndpoint.value.path;
  for (const [k, v] of Object.entries(pathParamValues.value)) {
    path = path.replace(`{${k}}`, v || `{${k}}`);
  }
  const qp = queryParams.value.filter((q) => q.key.trim());
  if (qp.length) {
    path += "?" + qp.map((q) => `${encodeURIComponent(q.key)}=${encodeURIComponent(q.value)}`).join("&");
  }
  return path;
}

function methodColor(method: string): string {
  const colors: Record<string, string> = {
    GET: "bg-blue-100 text-blue-800",
    POST: "bg-green-100 text-green-800",
    PUT: "bg-amber-100 text-amber-800",
    PATCH: "bg-orange-100 text-orange-800",
    DELETE: "bg-red-100 text-red-800",
  };
  return colors[method] ?? "bg-gray-100 text-gray-800";
}

async function sendRequest() {
  if (!selectedEndpoint.value) return;
  const path = resolvedPath();
  const method = requestMethod.value;
  const hasBody = method !== "GET" && method !== "DELETE" && requestBodyText.value.trim();

  apiLoading.value = true;
  apiResponse.value = null;

  const start = Date.now();

  // Use raw fetch so we can capture non-2xx responses too
  try {
    const xsrf = document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1];
    const headers: Record<string, string> = { Accept: "application/json" };
    if (xsrf) headers["X-XSRF-TOKEN"] = decodeURIComponent(xsrf);
    if (hasBody) headers["Content-Type"] = "application/json";

    const baseUrl = API_BASE_URL;

    const res = await fetch(`${baseUrl}${path}`, {
      method,
      credentials: "include",
      headers,
      ...(hasBody ? { body: requestBodyText.value } : {}),
    });

    const ms = Date.now() - start;
    let body: unknown;
    try { body = await res.json(); } catch { body = null; }

    apiResponse.value = { status: res.status, ms, body, ok: res.ok };
  } catch (e: unknown) {
    apiResponse.value = { status: 0, ms: Date.now() - start, body: String(e), ok: false };
  } finally {
    apiLoading.value = false;
  }
}

async function copyResponse() {
  await navigator.clipboard.writeText(JSON.stringify(apiResponse.value?.body, null, 2));
  copiedResponse.value = true;
  setTimeout(() => (copiedResponse.value = false), 2000);
}

function statusColor(status: number): string {
  if (status >= 200 && status < 300) return "bg-green-100 text-green-800";
  if (status >= 400) return "bg-red-100 text-red-800";
  return "bg-gray-100 text-gray-700";
}

// ──────────────────────────────────────────────────────────────────────────────
// TAB 4 — DB Browser
// ──────────────────────────────────────────────────────────────────────────────
const dbTables = ref<{ table: string; count: number }[]>([]);
const dbTablesLoading = ref(false);
const selectedTable = ref<string | null>(null);
const tableRows = ref<Record<string, unknown>[]>([]);
const tableLoading = ref(false);
const tablePage = ref(1);
const tableLimit = ref(20);
const tableTotal = ref(0);
const tableTotalPages = ref(0);
const tableSearch = ref("");
let tableSearchTimer: ReturnType<typeof setTimeout>;
const editingRowId = ref<number | null>(null);
const editingRowData = ref<Record<string, unknown>>({});
const showNewRowModal = ref(false);
const newRowData = ref<Record<string, unknown>>({});
const savingRow = ref(false);

async function loadDbTables() {
  dbTablesLoading.value = true;
  try {
    const res = await devTables();
    dbTables.value = res.data;
  } catch {
    toast.error("Failed to load tables");
  } finally {
    dbTablesLoading.value = false;
  }
}

async function loadTableRows() {
  if (!selectedTable.value) return;
  tableLoading.value = true;
  try {
    const params = new URLSearchParams({
      page: String(tablePage.value),
      limit: String(tableLimit.value),
    });
    if (tableSearch.value) params.set("q", tableSearch.value);
    const res = await devTableRows(selectedTable.value, `?${params.toString()}`);
    tableRows.value = res.data as Record<string, unknown>[];
    tableTotal.value = (res.meta?.total as number) ?? 0;
    tableTotalPages.value = (res.meta?.totalPages as number) ?? 0;
  } catch {
    toast.error("Failed to load rows");
  } finally {
    tableLoading.value = false;
  }
}

function selectTable(name: string) {
  selectedTable.value = name;
  tablePage.value = 1;
  tableSearch.value = "";
  editingRowId.value = null;
  loadTableRows();
}

const tableColumns = computed(() => {
  if (!tableRows.value.length) return [];
  return Object.keys(tableRows.value[0]);
});

function isJsonColumn(val: unknown): boolean {
  if (typeof val === "object" && val !== null) return true;
  if (typeof val === "string" && (val.startsWith("{") || val.startsWith("["))) return true;
  return false;
}

function displayValue(val: unknown): string {
  if (val === null || val === undefined) return "–";
  if (typeof val === "object") return JSON.stringify(val);
  return String(val);
}

function cellClass(col: string): string {
  if (col === "id") return "w-12 text-center";
  if (col.endsWith("_at") || col.endsWith("_time")) return "w-36";
  if (col === "password" || col === "remember_token") return "w-24";
  return "";
}

function cellDisplayVal(row: Record<string, unknown>, col: string): string {
  const val = row[col];
  if (col === "password") return "••••••••";
  if (col === "remember_token" && val) return "****";
  return displayValue(val);
}

function startEdit(row: Record<string, unknown>) {
  editingRowId.value = row.id as number;
  editingRowData.value = { ...row };
}

function cancelEdit() {
  editingRowId.value = null;
  editingRowData.value = {};
}

async function saveEdit() {
  if (!selectedTable.value || editingRowId.value === null) return;
  savingRow.value = true;
  try {
    const data = { ...editingRowData.value };
    delete data.id;
    delete data.created_at;
    await devUpdateRow(selectedTable.value, editingRowId.value, data);
    toast.success("Row updated");
    editingRowId.value = null;
    await loadTableRows();
    // Refresh count
    const idx = dbTables.value.findIndex((t) => t.table === selectedTable.value);
    if (idx >= 0) {
      const res = await devTables();
      dbTables.value = res.data;
    }
  } catch {
    toast.error("Failed to update row");
  } finally {
    savingRow.value = false;
  }
}

async function deleteRow(id: number) {
  if (!selectedTable.value) return;
  const accepted = await confirm({
    title: "Delete Row",
    message: `Delete row #${id} from ${selectedTable.value}? This cannot be undone.`,
    confirmText: "Delete",
    destructive: true,
  });
  if (!accepted) return;
  try {
    await devDeleteRow(selectedTable.value!, id);
    toast.success("Row deleted");
    await loadTableRows();
    const res = await devTables();
    dbTables.value = res.data;
  } catch {
    toast.error("Failed to delete row");
  }
}

function openNewRowModal() {
  newRowData.value = {};
  if (tableColumns.value.length) {
    tableColumns.value
      .filter((c) => c !== "id" && c !== "created_at" && c !== "updated_at")
      .forEach((c) => (newRowData.value[c] = ""));
  }
  showNewRowModal.value = true;
}

async function submitNewRow() {
  if (!selectedTable.value) return;
  savingRow.value = true;
  try {
    await devCreateRow(selectedTable.value, newRowData.value);
    toast.success("Row created");
    showNewRowModal.value = false;
    tablePage.value = 1;
    await loadTableRows();
    const res = await devTables();
    dbTables.value = res.data;
  } catch {
    toast.error("Failed to create row");
  } finally {
    savingRow.value = false;
  }
}

function onTableSearchInput() {
  clearTimeout(tableSearchTimer);
  tableSearchTimer = setTimeout(() => {
    tablePage.value = 1;
    loadTableRows();
  }, 400);
}

watch(tableLimit, () => {
  tablePage.value = 1;
  loadTableRows();
});

// ── Mount ──────────────────────────────────────────────────────────────────
onMounted(async () => {
  await loadSysInfo();
  await loadLiveUser();
  await loadDbTables();
});
</script>

<template>
  <AdminLayout>
    <!-- Warning Banner -->
    <div :class="['border-b px-6 py-3 flex items-center gap-3', bannerColor]">
      <AlertTriangle class="w-5 h-5 flex-shrink-0" />
      <span class="font-semibold text-sm">
        DEV CONSOLE — This page exposes sensitive system internals. Remove before production.
      </span>
      <span
        v-if="sysInfo"
        :class="['ml-auto px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wide', envColor]"
      >
        {{ sysInfo.app_env }}
      </span>
    </div>

    <div class="p-6">
      <!-- Tab Bar -->
      <div class="flex gap-2 mb-6 border-b pb-3">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors',
            activeTab === tab.id
              ? 'bg-gray-900 text-white'
              : 'text-gray-600 hover:bg-gray-100',
          ]"
        >
          <component :is="tab.icon" class="w-4 h-4" />
          {{ tab.label }}
        </button>
      </div>

      <!-- ──────────────────── TAB 1: System Info ─────────────────────── -->
      <div v-if="activeTab === 'sysinfo'">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold">System Information</h2>
          <button
            @click="loadSysInfo"
            :disabled="sysLoading"
            class="flex items-center gap-2 px-3 py-1.5 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg"
          >
            <RefreshCw :class="['w-4 h-4', sysLoading && 'animate-spin']" />
            Refresh
          </button>
        </div>

        <div v-if="sysLoading" class="text-center py-16 text-gray-400">Loading…</div>

        <div v-else-if="sysInfo" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- App -->
          <div class="bg-white border rounded-xl p-5 shadow-sm">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Application</h3>
            <dl class="space-y-2">
              <div class="flex justify-between">
                <dt class="text-sm text-gray-500">Environment</dt>
                <dd>
                  <span :class="['px-2 py-0.5 rounded text-xs font-bold uppercase', envColor]">
                    {{ sysInfo.app_env }}
                  </span>
                </dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-sm text-gray-500">Debug Mode</dt>
                <dd>
                  <span :class="['px-2 py-0.5 rounded text-xs font-semibold', sysInfo.app_debug ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600']">
                    {{ sysInfo.app_debug ? 'ON' : 'OFF' }}
                  </span>
                </dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-sm text-gray-500">App URL</dt>
                <dd class="text-sm font-mono text-gray-800 truncate max-w-48">{{ sysInfo.app_url }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-sm text-gray-500">PHP Version</dt>
                <dd class="text-sm font-mono text-gray-800">{{ sysInfo.php_version }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-sm text-gray-500">Laravel Version</dt>
                <dd class="text-sm font-mono text-gray-800">{{ sysInfo.laravel_version }}</dd>
              </div>
            </dl>
          </div>

          <!-- Database -->
          <div class="bg-white border rounded-xl p-5 shadow-sm">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Database</h3>
            <dl v-if="dbInfo" class="space-y-2">
              <div class="flex justify-between">
                <dt class="text-sm text-gray-500">Driver</dt>
                <dd>
                  <span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded text-xs font-bold uppercase">
                    {{ dbInfo.driver }}
                  </span>
                </dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-sm text-gray-500">Connected</dt>
                <dd>
                  <span :class="['px-2 py-0.5 rounded text-xs font-semibold', dbInfo.connected ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']">
                    {{ dbInfo.connected ? 'YES' : 'NO' }}
                  </span>
                </dd>
              </div>
              <!-- SQLite -->
              <template v-if="dbInfo.driver === 'sqlite'">
                <div class="flex justify-between">
                  <dt class="text-sm text-gray-500">Path</dt>
                  <dd class="text-xs font-mono text-gray-800 truncate max-w-56" :title="String(dbInfo.path)">{{ dbInfo.path }}</dd>
                </div>
              </template>
              <!-- PostgreSQL / MySQL -->
              <template v-else>
                <div class="flex justify-between">
                  <dt class="text-sm text-gray-500">Host</dt>
                  <dd class="text-sm font-mono text-gray-800">{{ dbInfo.host }}</dd>
                </div>
                <div class="flex justify-between">
                  <dt class="text-sm text-gray-500">Port</dt>
                  <dd class="text-sm font-mono text-gray-800">{{ dbInfo.port }}</dd>
                </div>
                <div class="flex justify-between">
                  <dt class="text-sm text-gray-500">Database</dt>
                  <dd class="text-sm font-mono text-gray-800">{{ dbInfo.database }}</dd>
                </div>
                <div class="flex justify-between">
                  <dt class="text-sm text-gray-500">Username</dt>
                  <dd class="text-sm font-mono text-gray-800">{{ dbInfo.username }}</dd>
                </div>
              </template>
            </dl>
          </div>

          <!-- Drivers -->
          <div class="bg-white border rounded-xl p-5 shadow-sm">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Drivers</h3>
            <dl class="space-y-2">
              <div class="flex justify-between">
                <dt class="text-sm text-gray-500">Cache</dt>
                <dd class="text-sm font-mono text-gray-800">{{ sysInfo.cache_driver }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-sm text-gray-500">Queue</dt>
                <dd class="text-sm font-mono text-gray-800">{{ sysInfo.queue_driver }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-sm text-gray-500">Session</dt>
                <dd class="text-sm font-mono text-gray-800">{{ sysInfo.session_driver }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-sm text-gray-500">Filesystem</dt>
                <dd class="text-sm font-mono text-gray-800">{{ sysInfo.filesystem_disk }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <!-- ──────────────────── TAB 2: Auth & Session ───────────────────── -->
      <div v-if="activeTab === 'auth'">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold">Auth & Session</h2>
          <div class="flex gap-2">
            <button
              @click="loadLiveUser"
              :disabled="authLoading"
              class="flex items-center gap-2 px-3 py-1.5 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg"
            >
              <RefreshCw :class="['w-4 h-4', authLoading && 'animate-spin']" />
              Re-fetch
            </button>
            <button
              @click="copyAuthJson"
              class="flex items-center gap-2 px-3 py-1.5 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg"
            >
              <component :is="copiedAuth ? Check : Copy" class="w-4 h-4" />
              {{ copiedAuth ? 'Copied!' : 'Copy JSON' }}
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- User Info -->
          <div class="bg-white border rounded-xl p-5 shadow-sm">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Current User (Live)</h3>
            <div v-if="authLoading" class="text-sm text-gray-400">Loading…</div>
            <dl v-else-if="liveUser" class="space-y-2">
              <div v-for="(val, key) in liveUser" :key="key" class="flex justify-between gap-4">
                <dt class="text-sm text-gray-500 flex-shrink-0">{{ key }}</dt>
                <dd class="text-sm font-mono text-gray-800 truncate">{{ val ?? '–' }}</dd>
              </div>
            </dl>
          </div>

          <!-- Pinia Store -->
          <div class="bg-white border rounded-xl p-5 shadow-sm">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Auth Store (Pinia)</h3>
            <dl class="space-y-2">
              <div v-if="authUser" v-for="(val, key) in authUser" :key="key" class="flex justify-between gap-4">
                <dt class="text-sm text-gray-500 flex-shrink-0">{{ key }}</dt>
                <dd class="text-sm font-mono text-gray-800 truncate">{{ val ?? '–' }}</dd>
              </div>
              <div v-else class="text-sm text-gray-400">Not authenticated</div>
            </dl>
          </div>

          <!-- Session Cookies -->
          <div class="bg-white border rounded-xl p-5 shadow-sm md:col-span-2">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Session & CSRF</h3>
            <dl class="space-y-3">
              <div class="flex justify-between items-center">
                <dt class="text-sm text-gray-500">XSRF-TOKEN (masked)</dt>
                <dd class="text-xs font-mono bg-gray-100 px-2 py-1 rounded text-gray-700">{{ maskToken(getXsrfToken()) }}</dd>
              </div>
              <div class="flex justify-between items-center">
                <dt class="text-sm text-gray-500">XSRF-TOKEN (full)</dt>
                <dd class="text-xs font-mono bg-gray-100 px-2 py-1 rounded text-gray-700 truncate max-w-96">{{ getXsrfToken() }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <!-- ──────────────────── TAB 3: API Tester ───────────────────────── -->
      <div v-if="activeTab === 'api'" class="flex gap-4 h-[calc(100vh-260px)] min-h-[500px]">
        <!-- Left: Endpoint List -->
        <div class="w-72 flex-shrink-0 flex flex-col border rounded-xl overflow-hidden bg-white shadow-sm">
          <div class="p-3 border-b">
            <input
              v-model="apiSearch"
              placeholder="Search endpoints…"
              class="w-full text-sm border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-gray-300"
            />
          </div>
          <div class="overflow-y-auto flex-1">
            <div v-for="(eps, group) in filteredGroups" :key="group">
              <div class="px-3 py-1.5 text-xs font-semibold text-gray-400 uppercase tracking-wider bg-gray-50 sticky top-0 border-b">
                {{ group }}
              </div>
              <button
                v-for="ep in eps"
                :key="ep.method + ep.path"
                @click="selectEndpoint(ep)"
                :class="[
                  'w-full text-left px-3 py-2 border-b flex items-start gap-2 hover:bg-gray-50 transition-colors',
                  selectedEndpoint === ep ? 'bg-blue-50 border-l-2 border-l-blue-500' : '',
                ]"
              >
                <span :class="['px-1.5 py-0.5 rounded text-xs font-bold flex-shrink-0 mt-0.5', methodColor(ep.method)]">
                  {{ ep.method }}
                </span>
                <span class="text-xs font-mono text-gray-700 break-all leading-5">{{ ep.path }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Right: Request Builder + Response -->
        <div class="flex-1 flex flex-col gap-3 min-w-0 overflow-y-auto">
          <div v-if="!selectedEndpoint" class="flex-1 flex items-center justify-center text-gray-400 bg-white border rounded-xl shadow-sm">
            <div class="text-center">
              <Zap class="w-10 h-10 mx-auto mb-3 opacity-30" />
              <p class="text-sm">Select an endpoint from the left to get started</p>
            </div>
          </div>

          <template v-else>
            <!-- Endpoint Header -->
            <div class="bg-white border rounded-xl p-4 shadow-sm">
              <div class="flex items-center gap-3 mb-1">
                <span :class="['px-2 py-1 rounded text-sm font-bold', methodColor(requestMethod)]">
                  {{ requestMethod }}
                </span>
                <span class="font-mono text-sm text-gray-800 truncate flex-1">{{ resolvedPath() }}</span>
                <button
                  @click="sendRequest"
                  :disabled="apiLoading"
                  class="flex items-center gap-2 px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-700 disabled:opacity-50"
                >
                  <Send class="w-4 h-4" />
                  {{ apiLoading ? 'Sending…' : 'Send' }}
                </button>
              </div>
              <p class="text-xs text-gray-400">{{ selectedEndpoint.description }}</p>
            </div>

            <!-- Path Params -->
            <div v-if="Object.keys(pathParamValues).length" class="bg-white border rounded-xl p-4 shadow-sm">
              <h4 class="text-xs font-semibold text-gray-400 uppercase mb-3">Path Parameters</h4>
              <div class="grid grid-cols-2 gap-3">
                <div v-for="(_, key) in pathParamValues" :key="key">
                  <label class="text-xs text-gray-500 mb-1 block">{{ key }}</label>
                  <input
                    v-model="pathParamValues[key]"
                    :placeholder="`{${key}}`"
                    class="w-full font-mono text-sm border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-gray-300"
                  />
                </div>
              </div>
            </div>

            <!-- Query Params -->
            <div v-if="requestMethod === 'GET'" class="bg-white border rounded-xl p-4 shadow-sm">
              <div class="flex items-center justify-between mb-3">
                <h4 class="text-xs font-semibold text-gray-400 uppercase">Query Params</h4>
                <button @click="queryParams.push({ key: '', value: '' })" class="text-xs text-blue-600 hover:underline flex items-center gap-1">
                  <Plus class="w-3 h-3" /> Add
                </button>
              </div>
              <div v-for="(qp, idx) in queryParams" :key="idx" class="flex items-center gap-2 mb-2">
                <input v-model="qp.key"   placeholder="key"   class="flex-1 text-sm font-mono border rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-gray-300" />
                <input v-model="qp.value" placeholder="value" class="flex-1 text-sm font-mono border rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-gray-300" />
                <button @click="queryParams.splice(idx, 1)" class="text-gray-400 hover:text-red-500"><X class="w-4 h-4" /></button>
              </div>
            </div>

            <!-- Request Body -->
            <div v-if="requestMethod !== 'GET' && requestMethod !== 'DELETE'" class="bg-white border rounded-xl p-4 shadow-sm">
              <h4 class="text-xs font-semibold text-gray-400 uppercase mb-3">Request Body (JSON)</h4>
              <textarea
                v-model="requestBodyText"
                rows="8"
                class="w-full font-mono text-sm border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-gray-300 resize-y"
                placeholder="{}"
              />
            </div>

            <!-- Response -->
            <div v-if="apiResponse" class="bg-white border rounded-xl p-4 shadow-sm">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                  <h4 class="text-xs font-semibold text-gray-400 uppercase">Response</h4>
                  <span :class="['px-2 py-0.5 rounded text-sm font-bold font-mono', statusColor(apiResponse.status)]">
                    {{ apiResponse.status }}
                  </span>
                  <span class="text-xs text-gray-400">{{ apiResponse.ms }}ms</span>
                </div>
                <button
                  @click="copyResponse"
                  class="flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700"
                >
                  <component :is="copiedResponse ? Check : Copy" class="w-3.5 h-3.5" />
                  {{ copiedResponse ? 'Copied' : 'Copy' }}
                </button>
              </div>
              <pre class="text-xs font-mono bg-gray-50 rounded-lg p-3 overflow-auto max-h-80 text-gray-800">{{ JSON.stringify(apiResponse.body, null, 2) }}</pre>
            </div>
          </template>
        </div>
      </div>

      <!-- ──────────────────── TAB 4: DB Browser ───────────────────────── -->
      <div v-if="activeTab === 'db'" class="flex gap-4 h-[calc(100vh-260px)] min-h-[500px]">
        <!-- Left: Tables List -->
        <div class="w-56 flex-shrink-0 flex flex-col border rounded-xl overflow-hidden bg-white shadow-sm">
          <div class="px-3 py-2 border-b bg-gray-50 flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-500 uppercase">Tables</span>
            <button @click="loadDbTables" :disabled="dbTablesLoading">
              <RefreshCw :class="['w-3.5 h-3.5 text-gray-400', dbTablesLoading && 'animate-spin']" />
            </button>
          </div>
          <div class="overflow-y-auto flex-1">
            <div v-if="dbTablesLoading" class="p-4 text-sm text-gray-400 text-center">Loading…</div>
            <button
              v-for="t in dbTables"
              :key="t.table"
              @click="selectTable(t.table)"
              :class="[
                'w-full text-left px-3 py-2 border-b flex items-center justify-between hover:bg-gray-50 transition-colors',
                selectedTable === t.table ? 'bg-blue-50 border-l-2 border-l-blue-500 font-semibold' : '',
              ]"
            >
              <span class="text-sm text-gray-800 truncate">{{ t.table }}</span>
              <span class="text-xs text-gray-400 flex-shrink-0">{{ t.count }}</span>
            </button>
          </div>
        </div>

        <!-- Right: Table Viewer -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden border rounded-xl bg-white shadow-sm">
          <div v-if="!selectedTable" class="flex-1 flex items-center justify-center text-gray-400">
            <div class="text-center">
              <Database class="w-10 h-10 mx-auto mb-3 opacity-30" />
              <p class="text-sm">Select a table to browse its data</p>
            </div>
          </div>

          <template v-else>
            <!-- Toolbar -->
            <div class="px-4 py-3 border-b flex items-center gap-3 flex-shrink-0">
              <span class="font-semibold text-sm">{{ selectedTable }}</span>
              <span class="text-xs text-gray-400">{{ tableTotal }} rows</span>
              <div class="flex-1" />
              <input
                v-model="tableSearch"
                @input="onTableSearchInput"
                placeholder="Search…"
                class="text-sm border rounded-lg px-3 py-1.5 w-44 focus:outline-none focus:ring-2 focus:ring-gray-300"
              />
              <select
                v-model="tableLimit"
                class="text-sm border rounded-lg px-2 py-1.5 focus:outline-none"
              >
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="50">50</option>
              </select>
              <button
                @click="openNewRowModal"
                class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-900 text-white text-sm rounded-lg hover:bg-gray-700"
              >
                <Plus class="w-4 h-4" /> New Row
              </button>
            </div>

            <!-- Table -->
            <div class="flex-1 overflow-auto">
              <div v-if="tableLoading" class="flex items-center justify-center h-32 text-gray-400">Loading…</div>
              <table v-else-if="tableRows.length" class="w-full text-xs">
                <thead class="bg-gray-50 sticky top-0 z-10">
                  <tr>
                    <th v-for="col in tableColumns" :key="col" :class="['px-3 py-2 text-left font-semibold text-gray-500 border-b whitespace-nowrap', cellClass(col)]">
                      {{ col }}
                    </th>
                    <th class="px-3 py-2 text-right font-semibold text-gray-500 border-b w-20">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="row in tableRows"
                    :key="String(row.id)"
                    class="border-b hover:bg-gray-50 transition-colors"
                  >
                    <template v-if="editingRowId === row.id">
                      <td v-for="col in tableColumns" :key="col" class="px-2 py-1.5">
                        <template v-if="col === 'id' || col === 'created_at'">
                          <span class="font-mono text-gray-400 text-xs">{{ cellDisplayVal(row, col) }}</span>
                        </template>
                        <template v-else-if="col === 'password'">
                          <span class="font-mono text-gray-400 text-xs">••••••••</span>
                        </template>
                        <template v-else-if="isJsonColumn(row[col])">
                          <textarea
                            v-model="editingRowData[col] as string"
                            rows="2"
                            class="w-full font-mono border rounded px-1.5 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400 min-w-28"
                          />
                        </template>
                        <template v-else>
                          <input
                            v-model="editingRowData[col] as string"
                            class="w-full font-mono border rounded px-1.5 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400 min-w-20"
                          />
                        </template>
                      </td>
                      <td class="px-2 py-1.5 text-right">
                        <div class="flex items-center justify-end gap-1">
                          <button @click="saveEdit" :disabled="savingRow" class="px-2 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700 disabled:opacity-50">
                            Save
                          </button>
                          <button @click="cancelEdit" class="px-2 py-1 bg-gray-200 rounded text-xs hover:bg-gray-300">
                            <X class="w-3 h-3" />
                          </button>
                        </div>
                      </td>
                    </template>
                    <template v-else>
                      <td v-for="col in tableColumns" :key="col" class="px-3 py-2">
                        <span :class="['font-mono truncate block', col === 'password' ? 'text-gray-300' : 'text-gray-700']" style="max-width: 200px">
                          {{ cellDisplayVal(row, col) }}
                        </span>
                      </td>
                      <td class="px-3 py-2 text-right">
                        <div class="flex items-center justify-end gap-2">
                          <button @click="startEdit(row)" class="text-gray-400 hover:text-blue-600">
                            <Pencil class="w-3.5 h-3.5" />
                          </button>
                          <button @click="deleteRow(row.id as number)" class="text-gray-400 hover:text-red-600">
                            <Trash2 class="w-3.5 h-3.5" />
                          </button>
                        </div>
                      </td>
                    </template>
                  </tr>
                </tbody>
              </table>
              <div v-else class="flex items-center justify-center h-32 text-gray-400 text-sm">No rows found</div>
            </div>

            <!-- Pagination -->
            <div class="px-4 py-2 border-t bg-gray-50 flex items-center gap-3 text-sm flex-shrink-0">
              <span class="text-gray-500 text-xs">
                Page {{ tablePage }} of {{ tableTotalPages }} ({{ tableTotal }} total)
              </span>
              <div class="flex-1" />
              <button
                @click="tablePage--; loadTableRows()"
                :disabled="tablePage <= 1"
                class="p-1 rounded hover:bg-gray-200 disabled:opacity-30"
              >
                <ChevronLeft class="w-4 h-4" />
              </button>
              <button
                @click="tablePage++; loadTableRows()"
                :disabled="tablePage >= tableTotalPages"
                class="p-1 rounded hover:bg-gray-200 disabled:opacity-30"
              >
                <ChevronRight class="w-4 h-4" />
              </button>
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- New Row Modal -->
    <Teleport to="body">
      <div v-if="showNewRowModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[80vh] flex flex-col">
          <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="font-semibold text-lg">New Row — {{ selectedTable }}</h3>
            <button @click="showNewRowModal = false" class="text-gray-400 hover:text-gray-600">
              <X class="w-5 h-5" />
            </button>
          </div>
          <div class="overflow-y-auto flex-1 px-6 py-4 space-y-3">
            <div v-for="(val, key) in newRowData" :key="key">
              <label class="text-sm text-gray-600 block mb-1">{{ key }}</label>
              <input
                v-model="newRowData[key] as string"
                class="w-full font-mono text-sm border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-gray-300"
                :placeholder="String(key)"
              />
            </div>
          </div>
          <div class="px-6 py-4 border-t flex justify-end gap-3">
            <button @click="showNewRowModal = false" class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg">Cancel</button>
            <button
              @click="submitNewRow"
              :disabled="savingRow"
              class="px-4 py-2 text-sm bg-gray-900 text-white rounded-lg hover:bg-gray-700 disabled:opacity-50"
            >
              {{ savingRow ? 'Creating…' : 'Create Row' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </AdminLayout>
</template>
