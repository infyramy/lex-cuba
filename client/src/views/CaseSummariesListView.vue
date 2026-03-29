<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import {
  BookOpen,
  Plus,
  Search,
  Pencil,
  Trash2,
  CheckCircle2,
  XCircle,
  FileText,
} from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import { listCaseSummaries, deleteCaseSummary } from "@/api/cms";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import { useToast } from "@/composables/useToast";
import type { CaseSummary } from "@/types";

const router = useRouter();
const toast = useToast();
const confirmDialog = useConfirmDialog();

const rows = ref<CaseSummary[]>([]);
const loading = ref(true);
const q = ref("");

async function load() {
  loading.value = true;
  try {
    const params = new URLSearchParams({ page: "1", limit: "100" });
    if (q.value) params.set("q", q.value);
    const response = await listCaseSummaries(`?${params.toString()}`);
    rows.value = response.data;
  } catch (e) {
    toast.error("Load failed", e instanceof Error ? e.message : "Unable to load case summaries.");
  } finally {
    loading.value = false;
  }
}

let searchTimer: ReturnType<typeof setTimeout>;
function onSearchInput() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => { load(); }, 300);
}

async function remove(id: number) {
  const allowed = await confirmDialog.confirm({
    title: "Delete case summary?",
    message: "This action cannot be undone.",
    confirmText: "Delete",
    destructive: true,
  });
  if (!allowed) return;
  try {
    await deleteCaseSummary(id);
    await load();
    toast.success("Case summary deleted");
  } catch (e) {
    toast.error("Delete failed", e instanceof Error ? e.message : "Unable to delete case summary.");
  }
}

onMounted(load);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-6">
      <div class="admin-hero">
        <div class="space-y-2">
          <p class="page-kicker">Knowledge Library</p>
          <h1 class="page-title">Case Summaries</h1>
          <p class="page-subtitle">Review legal precedents, search citations, and keep published summaries organised with clearer table actions.</p>
        </div>
        <div class="admin-actions">
          <button class="admin-primary-button" @click="router.push('/admin/case-summaries/new')">
            <Plus class="h-4 w-4" />
            Add Case Summary
          </button>
        </div>
      </div>

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-2.5">
          <div class="flex items-center gap-2.5">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50">
              <BookOpen class="h-3.5 w-3.5 text-amber-600" />
            </div>
            <h2 class="text-sm font-semibold text-slate-900">All Case Summaries</h2>
            <span class="admin-pill bg-slate-100 text-slate-500">{{ rows.length }}</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="relative">
              <Search class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
              <input
                v-model="q"
                placeholder="Search case summaries..."
                class="w-full sm:w-64 rounded-lg border border-slate-300 py-1.5 pl-9 pr-3 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                @keyup.enter="load"
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
                <th class="px-4 py-2 text-xs font-semibold text-slate-500">Citation</th>
                <th class="px-4 py-2 text-xs font-semibold text-slate-500">Category</th>
                <th class="px-4 py-2 text-xs font-semibold text-slate-500">Published</th>
                <th class="px-4 py-2 text-xs font-semibold text-slate-500">Has PDF</th>
                <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="loading">
                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-400">Loading...</td>
              </tr>
              <tr v-else-if="rows.length === 0">
                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-400">No case summaries found.</td>
              </tr>
              <tr v-for="item in rows" v-else :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-medium text-slate-900">
                  <router-link :to="`/admin/case-summaries/${item.id}`" class="hover:text-violet-600">{{ item.title }}</router-link>
                </td>
                <td class="max-w-[200px] truncate px-4 py-2 text-sm text-slate-500">{{ item.citation }}</td>
                <td class="px-4 py-2">
                  <span v-if="item.category" class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">{{ item.category.name }}</span>
                  <span v-else class="text-xs text-slate-400">--</span>
                </td>
                <td class="px-4 py-2">
                  <span v-if="item.isPublished" class="admin-pill bg-emerald-100 text-emerald-700">
                    <CheckCircle2 class="h-3 w-3" /> Published
                  </span>
                  <span v-else class="admin-pill bg-slate-100 text-slate-500">
                    <XCircle class="h-3 w-3" /> Draft
                  </span>
                </td>
                <td class="px-4 py-2">
                  <span v-if="item.pdfFilePath" class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                    <FileText class="h-3 w-3" /> PDF
                  </span>
                  <span v-else class="text-xs text-slate-400">--</span>
                </td>
                <td class="px-4 py-2 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button
                      class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                      @click="router.push(`/admin/case-summaries/${item.id}`)"
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
      </article>
    </div>
  </AdminLayout>
</template>
