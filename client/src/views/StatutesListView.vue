<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import { useRouter } from "vue-router";
import {
  Scale,
  Plus,
  Search,
  Pencil,
  Trash2,
  CheckCircle2,
  XCircle,
  ExternalLink,
  FileText,
  ChevronLeft,
  ChevronRight,
} from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import { listStatutes, deleteStatute } from "@/api/cms";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import { useToast } from "@/composables/useToast";
import type { Statute, StatuteType } from "@/types";

const router = useRouter();
const toast = useToast();
const confirmDialog = useConfirmDialog();

const rows = ref<Statute[]>([]);
const loading = ref(true);
const activeTab = ref<"all" | StatuteType>("all");
const q = ref("");
const publishedFilter = ref<string>("");
const page = ref(1);
const totalPages = ref(1);
const total = ref(0);
const limit = 25;

function switchTab(tab: "all" | StatuteType) {
  activeTab.value = tab;
  page.value = 1;
  load();
}

async function load() {
  loading.value = true;
  try {
    const params = new URLSearchParams({ page: String(page.value), limit: String(limit) });
    if (q.value) params.set("q", q.value);
    if (activeTab.value !== "all") params.set("type", activeTab.value);
    if (publishedFilter.value !== "") params.set("is_published", publishedFilter.value);
    const response = await listStatutes(`?${params.toString()}`);
    rows.value = response.data;
    const meta = response.meta || {};
    totalPages.value = (meta.totalPages as number) || 1;
    total.value = (meta.total as number) || 0;
  } catch (e) {
    toast.error("Load failed", e instanceof Error ? e.message : "Unable to load statutes.");
  } finally {
    loading.value = false;
  }
}

function applyFilters() {
  page.value = 1;
  load();
}

let searchTimer: ReturnType<typeof setTimeout>;
function onSearchInput() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => { page.value = 1; load(); }, 300);
}

watch(publishedFilter, () => { page.value = 1; load(); });

function prevPage() {
  if (page.value > 1) { page.value--; load(); }
}

function nextPage() {
  if (page.value < totalPages.value) { page.value++; load(); }
}

async function remove(id: number) {
  const allowed = await confirmDialog.confirm({
    title: "Delete statute?",
    message: "This action cannot be undone.",
    confirmText: "Delete",
    destructive: true,
  });
  if (!allowed) return;
  try {
    await deleteStatute(id);
    await load();
    toast.success("Statute deleted");
  } catch (e) {
    toast.error("Delete failed", e instanceof Error ? e.message : "Unable to delete statute.");
  }
}

onMounted(load);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-6">
      <div class="admin-hero">
        <div class="space-y-2">
          <p class="page-kicker">Library Resources</p>
          <h1 class="page-title">Statutes</h1>
          <p class="page-subtitle">Manage statute documents and reference links for the mobile app library.</p>
        </div>
        <div class="admin-actions">
          <button class="admin-primary-button" @click="router.push('/admin/statutes/new')">
            <Plus class="h-4 w-4" />
            Add Statute
          </button>
        </div>
      </div>

      <!-- Tabs -->
      <div class="admin-tablist">
        <button
          class="admin-tab"
          :class="activeTab === 'all' ? 'admin-tab-active' : ''"
          @click="switchTab('all')"
        >
          All
        </button>
        <button
          class="admin-tab"
          :class="activeTab === 'link' ? 'admin-tab-active' : ''"
          @click="switchTab('link')"
        >
          Links
        </button>
        <button
          class="admin-tab"
          :class="activeTab === 'document' ? 'admin-tab-active' : ''"
          @click="switchTab('document')"
        >
          Documents
        </button>
      </div>

      <!-- Table card -->
      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-2.5">
          <div class="flex items-center gap-2.5">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-50">
              <Scale class="h-3.5 w-3.5 text-violet-600" />
            </div>
            <h2 class="text-sm font-semibold text-slate-900">
              {{ activeTab === 'all' ? 'All Statutes' : activeTab === 'link' ? 'Link Statutes' : 'Document Statutes' }}
            </h2>
            <span class="admin-pill bg-slate-100 text-slate-500">{{ total }}</span>
          </div>
          <div class="flex items-center gap-2">
            <select
              v-model="publishedFilter"
              class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
            >
              <option value="">All Status</option>
              <option value="1">Published</option>
              <option value="0">Draft</option>
            </select>
            <div class="relative">
              <Search class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
              <input
                v-model="q"
                placeholder="Search statutes..."
                class="w-full sm:w-64 rounded-lg border border-slate-300 py-1.5 pl-9 pr-3 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                @keyup.enter="applyFilters"
                @input="onSearchInput"
              />
            </div>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold text-slate-500">Title</th>
                <th class="px-4 py-2 text-xs font-semibold text-slate-500">Type</th>
                <th class="px-4 py-2 text-xs font-semibold text-slate-500">Published</th>
                <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="loading">
                <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-400">Loading...</td>
              </tr>
              <tr v-else-if="rows.length === 0">
                <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-400">No statutes found.</td>
              </tr>
              <tr v-for="item in rows" v-else :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-medium text-slate-900">
                  <router-link :to="`/admin/statutes/${item.id}`" class="hover:text-violet-600">{{ item.title }}</router-link>
                </td>
                <td class="px-4 py-2">
                  <span v-if="item.type === 'link'" class="admin-pill bg-blue-100 text-blue-700">
                    <ExternalLink class="h-3 w-3" /> Link
                  </span>
                  <span v-else class="admin-pill bg-amber-100 text-amber-700">
                    <FileText class="h-3 w-3" /> Document
                  </span>
                </td>
                <td class="px-4 py-2">
                  <span v-if="item.isPublished" class="admin-pill bg-emerald-100 text-emerald-700">
                    <CheckCircle2 class="h-3 w-3" /> Published
                  </span>
                  <span v-else class="admin-pill bg-slate-100 text-slate-500">
                    <XCircle class="h-3 w-3" /> Draft
                  </span>
                </td>
                <td class="px-4 py-2 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button
                      class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                      @click="router.push(`/admin/statutes/${item.id}`)"
                    >
                      <Pencil class="h-3.5 w-3.5" />
                      <span class="pointer-events-none absolute -bottom-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">Edit</span>
                    </button>
                    <button
                      class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600"
                      @click="remove(item.id)"
                    >
                      <Trash2 class="h-3.5 w-3.5" />
                      <span class="pointer-events-none absolute -bottom-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">Delete</span>
                    </button>
                  </div>
                </td>
              </tr>
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
