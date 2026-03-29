<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
  BookOpen,
  Save,
  ArrowLeft,
  Upload,
} from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import FormField from "@/components/FormField.vue";
import FileDropZone from "@/components/FileDropZone.vue";
import LoadingSkeleton from "@/components/LoadingSkeleton.vue";
import { Switch } from "@/components/ui/switch";
import { getCaseSummary, createCaseSummary, updateCaseSummary, listCategories } from "@/api/cms";
import { useToast } from "@/composables/useToast";
import { useFormDirty } from "@/composables/useFormDirty";
import { useFieldValidation } from "@/composables/useFieldValidation";
import type { Category } from "@/types";

const route = useRoute();
const router = useRouter();
const toast = useToast();

const isNew = computed(() => route.params.id === "new" || !route.params.id);
const id = computed(() => (isNew.value ? 0 : Number(route.params.id)));

const loading = ref(false);
const saving = ref(false);
const categories = ref<Category[]>([]);

const form = ref({
  title: "",
  citation: "",
  summaryText: "",
  categoryId: "" as number | "",
  isPublished: false,
});

const pdfFile = ref<File | null>(null);
const currentPdf = ref<{ name: string; type: string; size: number } | null>(null);

const { isDirty, resetDirty } = useFormDirty(form);
const { errors, validateAll, clearError } = useFieldValidation({
  title: { required: true, message: "Title is required" },
  citation: { required: true, message: "Citation is required" },
  summaryText: { required: true, message: "Summary text is required" },
});

async function load() {
  loading.value = true;
  try {
    const catRes = await listCategories("?limit=200&type=case_law");
    categories.value = catRes.data;

    if (!isNew.value) {
      const res = await getCaseSummary(id.value);
      const cs = res.data;
      form.value.title = cs.title;
      form.value.citation = cs.citation;
      form.value.summaryText = cs.summaryText;
      form.value.categoryId = cs.categoryId || "";
      form.value.isPublished = cs.isPublished;
      if (cs.pdfFilePath) {
        currentPdf.value = { name: "Current PDF", type: "application/pdf", size: 0 };
      }
    }
  } catch (e) {
    toast.error("Load failed", e instanceof Error ? e.message : "Unable to load case summary.");
  } finally {
    loading.value = false;
  }
}

async function save() {
  if (!validateAll(form.value)) return;

  saving.value = true;
  try {
    if (pdfFile.value) {
      // Use FormData when a PDF is attached
      const formData = new FormData();
      formData.append("title", form.value.title);
      formData.append("citation", form.value.citation);
      formData.append("summary_text", form.value.summaryText);
      if (form.value.categoryId) formData.append("category_id", String(form.value.categoryId));
      formData.append("is_published", form.value.isPublished ? "1" : "0");
      formData.append("pdf_file", pdfFile.value);

      if (isNew.value) {
        await createCaseSummary(formData);
        toast.success("Case summary created");
      } else {
        await updateCaseSummary(id.value, formData);
        toast.success("Case summary updated");
      }
    } else {
      const payload = {
        title: form.value.title,
        citation: form.value.citation,
        summaryText: form.value.summaryText,
        categoryId: form.value.categoryId || undefined,
        isPublished: form.value.isPublished,
      };

      if (isNew.value) {
        await createCaseSummary(payload);
        toast.success("Case summary created");
      } else {
        await updateCaseSummary(id.value, payload);
        toast.success("Case summary updated");
      }
    }
    resetDirty();
    router.push("/admin/case-summaries");
  } catch (e) {
    toast.error("Save failed", e instanceof Error ? e.message : "Unable to save case summary.");
  } finally {
    saving.value = false;
  }
}

onMounted(load);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-3xl space-y-6">
      <div class="admin-hero">
        <div class="space-y-2">
          <p class="page-kicker">Knowledge Library</p>
          <h1 class="page-title">{{ isNew ? 'Add Case Summary' : 'Edit Case Summary' }}</h1>
          <p class="page-subtitle">Draft legal summaries, attach supporting PDFs, and manage publication status in a roomier editor.</p>
        </div>
        <div class="admin-actions">
          <router-link to="/admin/case-summaries" class="admin-secondary-button">
            <ArrowLeft class="h-4 w-4" />
            Case Summaries
          </router-link>
        </div>
      </div>

      <div v-if="loading" class="py-12">
        <LoadingSkeleton variant="form" />
      </div>

      <template v-else>
        <div class="grid gap-4 lg:grid-cols-[1fr_280px]">
          <!-- LEFT COLUMN -->
          <div class="space-y-4">
            <!-- Case Details -->
            <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
              <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
                <BookOpen class="h-4 w-4 text-amber-600" />
                <h2 class="text-sm font-semibold text-slate-900">Case Details</h2>
              </div>
              <div class="space-y-3 p-4">
                <div class="grid gap-3 md:grid-cols-2">
                  <FormField label="Title" required :error="errors.title">
                    <input
                      v-model="form.title"
                      class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                      placeholder="Case title"
                      @input="() => clearError('title')"
                    />
                  </FormField>
                  <FormField label="Citation" required :error="errors.citation" helpText="Standard Malaysian case citation, e.g. [2024] MLJU 123">
                    <input
                      v-model="form.citation"
                      class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                      placeholder="e.g. [2024] MLJU 123"
                      @input="() => clearError('citation')"
                    />
                  </FormField>
                </div>
                <FormField label="Category">
                  <select
                    v-model="form.categoryId"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                  >
                    <option value="">No category</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                  </select>
                </FormField>
                <FormField label="Summary Text" required :error="errors.summaryText">
                  <textarea
                    v-model="form.summaryText"
                    rows="10"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                    placeholder="Full case summary..."
                    @input="() => clearError('summaryText')"
                  />
                </FormField>
              </div>
            </article>

            <!-- PDF Upload -->
            <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
              <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
                <Upload class="h-4 w-4 text-blue-600" />
                <h2 class="text-sm font-semibold text-slate-900">PDF File (Optional)</h2>
              </div>
              <div class="p-4">
                <FileDropZone
                  v-model="pdfFile"
                  accept=".pdf"
                  label="PDF files only, up to 10MB"
                  :currentFile="currentPdf"
                />
              </div>
            </article>
          </div>

          <!-- RIGHT COLUMN -->
          <div class="space-y-4">
            <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
              <div class="border-b border-slate-100 px-4 py-2.5">
                <h2 class="text-sm font-semibold text-slate-900">Publish</h2>
              </div>
              <div class="space-y-3 p-4">
                <label class="flex items-center justify-between">
                  <span class="text-sm text-slate-700">Published</span>
                  <Switch :checked="form.isPublished" @update:checked="form.isPublished = $event" />
                </label>
              </div>
            </article>
          </div>
        </div>

        <!-- ACTIONS -->
        <div class="flex items-center gap-3">
          <button
            class="flex items-center gap-2 rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 disabled:opacity-50"
            :disabled="saving || !form.title || !form.citation || !form.summaryText"
            @click="save"
          >
            <Save class="h-4 w-4" />
            {{ saving ? 'Saving...' : (isNew ? 'Create Case Summary' : 'Update Case Summary') }}
          </button>
          <router-link
            to="/admin/case-summaries"
            class="flex items-center gap-2 rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition-colors hover:bg-slate-50"
          >
            Cancel
          </router-link>
        </div>
      </template>
    </div>
  </AdminLayout>
</template>
