<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import { ScrollText, ChevronLeft, ChevronRight, ChevronDown, ChevronUp } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import { listAuditLogs } from "@/api/cms";
import type { AuditLog } from "@/types";

const logs = ref<AuditLog[]>([]);
const loading = ref(false);
const page = ref(1);
const totalPages = ref(1);
const total = ref(0);
const limit = 25;

// Active resource tab
const activeTab = ref("");

const tabs = [
  { key: "",                           label: "All" },
  { key: "App\\Models\\Note",          label: "Notes" },
  { key: "App\\Models\\CaseSummary",   label: "Cases" },
  { key: "App\\Models\\Question",      label: "Questions" },
  { key: "App\\Models\\Category",      label: "Topics" },
  { key: "App\\Models\\Member",        label: "Members" },
  { key: "App\\Models\\User",          label: "Users" },
  { key: "App\\Models\\Setting",       label: "Settings" },
  { key: "App\\Models\\Role",          label: "Roles" },
];

// Expanded row for detail view
const expandedId = ref<number | null>(null);

const actionColors: Record<string, string> = {
  created: "bg-emerald-100 text-emerald-700",
  updated: "bg-blue-100 text-blue-700",
  deleted: "bg-rose-100 text-rose-700",
  login:   "bg-violet-100 text-violet-700",
  logout:  "bg-slate-200 text-slate-600",
};

function typeLabel(fullType: string | null): string {
  if (!fullType) return "—";
  const parts = fullType.split("\\");
  return parts[parts.length - 1];
}

function buildParams(): string {
  const params = new URLSearchParams();
  params.set("page", String(page.value));
  params.set("limit", String(limit));
  if (activeTab.value) params.set("auditable_type", activeTab.value);
  return "?" + params.toString();
}

async function load() {
  loading.value = true;
  try {
    const res = await listAuditLogs(buildParams());
    logs.value = res.data.filter((log: AuditLog) => log.action !== "login" && log.action !== "logout");
    const meta = res.meta || {};
    totalPages.value = (meta.totalPages as number) || 1;
    total.value = (meta.total as number) || 0;
  } catch {
    logs.value = [];
  } finally {
    loading.value = false;
  }
}

function switchTab(key: string) {
  activeTab.value = key;
  page.value = 1;
}

function prevPage() {
  if (page.value > 1) { page.value--; load(); }
}

function nextPage() {
  if (page.value < totalPages.value) { page.value++; load(); }
}

function toggleExpand(id: number) {
  expandedId.value = expandedId.value === id ? null : id;
}

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleString("en-US", {
    month: "short", day: "numeric", year: "numeric",
    hour: "2-digit", minute: "2-digit",
  });
}

function formatJson(obj: Record<string, unknown> | null): string {
  if (!obj || Object.keys(obj).length === 0) return "—";
  return JSON.stringify(obj, null, 2);
}

watch(activeTab, load);
onMounted(load);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-6">
      <div class="admin-hero">
        <div class="space-y-2">
          <p class="page-kicker">Compliance</p>
          <h1 class="page-title">Audit Trail</h1>
          <p class="page-subtitle">Inspect administrative events and model changes filtered by resource type.</p>
        </div>
        <span class="admin-pill bg-slate-100 text-slate-600">{{ total }} entries</span>
      </div>

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <!-- Card header -->
        <div class="flex items-center gap-2.5 border-b border-slate-100 px-4 py-3">
          <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100">
            <ScrollText class="h-3.5 w-3.5 text-slate-600" />
          </div>
          <h2 class="text-sm font-semibold text-slate-900">Activity Log</h2>
          <span class="admin-pill bg-slate-100 text-slate-500">{{ total }}</span>
        </div>

        <!-- Resource tabs -->
        <div class="overflow-x-auto border-b border-slate-100">
          <div class="flex min-w-max gap-0.5 px-3 py-2">
            <button
              v-for="tab in tabs"
              :key="tab.key"
              class="rounded-md px-3 py-1.5 text-xs font-medium transition-colors"
              :class="activeTab === tab.key
                ? 'bg-slate-900 text-white'
                : 'text-slate-600 hover:bg-slate-100'"
              @click="switchTab(tab.key)"
            >
              {{ tab.label }}
            </button>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="px-4 py-10 text-center text-sm text-slate-400">Loading...</div>

        <!-- Empty -->
        <div v-else-if="logs.length === 0" class="px-4 py-10 text-center text-sm text-slate-400">No audit logs found.</div>

        <!-- Table -->
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 bg-slate-50/80 text-left">
                <th class="px-4 py-2.5 text-xs font-semibold text-slate-500">Action</th>
                <th class="px-4 py-2.5 text-xs font-semibold text-slate-500">Resource</th>
                <th class="px-4 py-2.5 text-xs font-semibold text-slate-500">Changed By</th>
                <th class="hidden px-4 py-2.5 text-xs font-semibold text-slate-500 sm:table-cell">IP Address</th>
                <th class="px-4 py-2.5 text-xs font-semibold text-slate-500">Date / Time</th>
                <th class="px-4 py-2.5 text-xs font-semibold text-slate-500">Details</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <template v-for="log in logs" :key="log.id">
                <tr class="transition-colors hover:bg-slate-50">
                  <!-- Action -->
                  <td class="px-4 py-2.5">
                    <span
                      class="rounded-full px-2 py-0.5 text-xs font-medium"
                      :class="actionColors[log.action] || 'bg-slate-100 text-slate-600'"
                    >{{ log.action }}</span>
                  </td>
                  <!-- Resource type + ID -->
                  <td class="px-4 py-2.5">
                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-xs font-mono text-slate-600">
                      {{ typeLabel(log.auditableType) }}
                      <template v-if="log.auditableId">#{{ log.auditableId }}</template>
                    </span>
                  </td>
                  <!-- User -->
                  <td class="px-4 py-2.5 text-sm text-slate-700">{{ log.user?.name || "System" }}</td>
                  <!-- IP -->
                  <td class="hidden px-4 py-2.5 text-xs text-slate-400 sm:table-cell">{{ log.ipAddress || "—" }}</td>
                  <!-- Date -->
                  <td class="whitespace-nowrap px-4 py-2.5 text-xs text-slate-400">{{ formatDate(log.createdAt) }}</td>
                  <!-- Details toggle -->
                  <td class="px-4 py-2.5">
                    <button
                      class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                      @click="toggleExpand(log.id)"
                    >
                      <component :is="expandedId === log.id ? ChevronUp : ChevronDown" class="h-3.5 w-3.5" />
                    </button>
                  </td>
                </tr>

                <!-- Expanded detail row -->
                <tr v-if="expandedId === log.id">
                  <td colspan="6" class="border-t border-slate-100 bg-slate-50 px-4 py-3">
                    <div class="grid gap-4 lg:grid-cols-2">
                      <div>
                        <p class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-slate-400">Old Values</p>
                        <pre class="max-h-44 overflow-auto rounded-lg border border-slate-200 bg-white p-3 text-xs text-slate-600">{{ formatJson(log.oldValues) }}</pre>
                      </div>
                      <div>
                        <p class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-slate-400">New Values</p>
                        <pre class="max-h-44 overflow-auto rounded-lg border border-slate-200 bg-white p-3 text-xs text-slate-600">{{ formatJson(log.newValues) }}</pre>
                      </div>
                    </div>
                    <p class="mt-2 truncate text-xs text-slate-400">UA: {{ log.userAgent || "—" }}</p>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="flex items-center justify-between border-t border-slate-100 px-4 py-2.5">
          <button
            class="flex items-center gap-1 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 disabled:opacity-40"
            :disabled="page <= 1"
            @click="prevPage"
          >
            <ChevronLeft class="h-3.5 w-3.5" />
            Previous
          </button>
          <span class="text-sm text-slate-500">Page {{ page }} of {{ totalPages }}</span>
          <button
            class="flex items-center gap-1 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 disabled:opacity-40"
            :disabled="page >= totalPages"
            @click="nextPage"
          >
            Next
            <ChevronRight class="h-3.5 w-3.5" />
          </button>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
