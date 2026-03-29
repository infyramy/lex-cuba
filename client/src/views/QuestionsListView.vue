<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import { useRouter } from "vue-router";
import {
  HelpCircle,
  Plus,
  Search,
  Pencil,
  Trash2,
  CheckCircle2,
  XCircle,
  FileText,
} from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import { listQuestionPapers, deleteQuestionPaper, listCategories } from "@/api/cms";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import { useToast } from "@/composables/useToast";
import type { QuestionPaper, QuestionPaperType, Category } from "@/types";

const router = useRouter();
const toast = useToast();
const confirmDialog = useConfirmDialog();

const rows = ref<QuestionPaper[]>([]);
const categories = ref<Category[]>([]);
const loading = ref(true);
const activeTab = ref<QuestionPaperType>("past_year");
const q = ref("");
const topicFilter = ref<number | "">("");

function formatFileSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

async function loadCategories() {
  try {
    const res = await listCategories("?limit=200");
    categories.value = res.data;
  } catch {
    // silent
  }
}

async function load() {
  loading.value = true;
  try {
    const params = new URLSearchParams({ page: "1", limit: "100", type: activeTab.value });
    if (q.value) params.set("q", q.value);
    if (activeTab.value === "topical" && topicFilter.value) {
      params.set("category_id", String(topicFilter.value));
    }
    const response = await listQuestionPapers(`?${params.toString()}`);
    rows.value = response.data;
  } catch (e) {
    toast.error("Load failed", e instanceof Error ? e.message : "Unable to load question papers.");
  } finally {
    loading.value = false;
  }
}

let searchTimer: ReturnType<typeof setTimeout>;
function onSearchInput() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => { load(); }, 300);
}

function switchTab(tab: QuestionPaperType) {
  activeTab.value = tab;
  q.value = "";
  topicFilter.value = "";
}

async function remove(id: number) {
  const allowed = await confirmDialog.confirm({
    title: "Delete question paper?",
    message: "This action cannot be undone.",
    confirmText: "Delete",
    destructive: true,
  });
  if (!allowed) return;
  try {
    await deleteQuestionPaper(id);
    await load();
    toast.success("Question paper deleted");
  } catch (e) {
    toast.error("Delete failed", e instanceof Error ? e.message : "Unable to delete question paper.");
  }
}

function topicName(paper: QuestionPaper): string {
  if (paper.category) return paper.category.name;
  const cat = categories.value.find((c) => c.id === paper.categoryId);
  return cat ? cat.name : "--";
}

watch(activeTab, () => load());
watch(topicFilter, () => load());

onMounted(async () => {
  await loadCategories();
  await load();
});
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-6">
      <div class="admin-hero">
        <div class="space-y-2">
          <p class="page-kicker">Question Bank</p>
          <h1 class="page-title">Question Papers</h1>
          <p class="page-subtitle">Manage past year and topical question papers. Upload PDFs for mobile access by subscribed users.</p>
        </div>
        <div class="admin-actions">
          <button class="admin-primary-button" @click="router.push('/admin/questions/new')">
            <Plus class="h-4 w-4" />
            Upload Paper
          </button>
        </div>
      </div>

      <!-- Tabs -->
      <div class="admin-tablist">
        <button
          class="admin-tab"
          :class="activeTab === 'past_year' ? 'admin-tab-active' : ''"
          @click="switchTab('past_year')"
        >
          Past Year
        </button>
        <button
          class="admin-tab"
          :class="activeTab === 'topical' ? 'admin-tab-active' : ''"
          @click="switchTab('topical')"
        >
          Topical
        </button>
      </div>

      <!-- Table card -->
      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-2.5">
          <div class="flex items-center gap-2.5">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50">
              <HelpCircle class="h-3.5 w-3.5 text-indigo-600" />
            </div>
            <h2 class="text-sm font-semibold text-slate-900">
              {{ activeTab === 'past_year' ? 'Past Year Papers' : 'Topical Papers' }}
            </h2>
            <span class="admin-pill bg-slate-100 text-slate-500">{{ rows.length }}</span>
          </div>
          <div class="flex items-center gap-2">
            <select
              v-if="activeTab === 'topical'"
              v-model="topicFilter"
              class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              @change="load"
            >
              <option value="">All Topics</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
            <div class="relative">
              <Search class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
              <input
                v-model="q"
                placeholder="Search papers..."
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
                <th v-if="activeTab === 'past_year'" class="px-4 py-2 text-xs font-semibold text-slate-500">Year</th>
                <th v-if="activeTab === 'topical'" class="px-4 py-2 text-xs font-semibold text-slate-500">Topic</th>
                <th class="px-4 py-2 text-xs font-semibold text-slate-500">File Name</th>
                <th class="px-4 py-2 text-xs font-semibold text-slate-500">Published</th>
                <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="loading">
                <td :colspan="5" class="px-4 py-6 text-center text-sm text-slate-400">Loading...</td>
              </tr>
              <tr v-else-if="rows.length === 0">
                <td :colspan="5" class="px-4 py-6 text-center text-sm text-slate-400">No question papers found.</td>
              </tr>
              <tr v-for="item in rows" v-else :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-medium text-slate-900">
                  <router-link :to="`/admin/questions/${item.id}`" class="hover:text-violet-600">{{ item.title }}</router-link>
                </td>
                <td v-if="activeTab === 'past_year'" class="px-4 py-2 text-slate-600">
                  {{ item.year ?? '--' }}
                </td>
                <td v-if="activeTab === 'topical'" class="px-4 py-2">
                  <span v-if="item.category || item.categoryId" class="rounded-full bg-violet-100 px-2.5 py-0.5 text-xs font-medium text-violet-700">{{ topicName(item) }}</span>
                  <span v-else class="text-xs text-slate-400">--</span>
                </td>
                <td class="max-w-[200px] truncate px-4 py-2 font-mono text-xs text-slate-500">
                  <div class="flex items-center gap-1.5">
                    <FileText class="h-3.5 w-3.5 shrink-0 text-slate-400" />
                    {{ item.fileName }}
                  </div>
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
                      @click="router.push(`/admin/questions/${item.id}`)"
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
