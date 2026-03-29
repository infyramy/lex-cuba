<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
  HelpCircle,
  Save,
  ArrowLeft,
  Upload,
} from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import FormField from "@/components/FormField.vue";
import FileDropZone from "@/components/FileDropZone.vue";
import LoadingSkeleton from "@/components/LoadingSkeleton.vue";
import { Switch } from "@/components/ui/switch";
import {
  getQuestionPaper,
  createQuestionPaper,
  updateQuestionPaper,
  uploadQuestionPaperFile,
  listCategories,
} from "@/api/cms";
import { useToast } from "@/composables/useToast";
import { useFormDirty } from "@/composables/useFormDirty";
import { useFieldValidation } from "@/composables/useFieldValidation";
import type { QuestionPaperType, Category } from "@/types";

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
  type: "past_year" as QuestionPaperType,
  year: "" as number | "",
  categoryId: "" as number | "",
  description: "",
  isPublished: false,
});

const file = ref<File | null>(null);
const currentFile = ref<{ name: string; type: string; size: number } | null>(null);

const { isDirty, resetDirty } = useFormDirty(form);
const { errors, validateAll, clearError } = useFieldValidation({
  title: { required: true, message: "Title is required" },
});

async function load() {
  loading.value = true;
  try {
    const catRes = await listCategories("?limit=200");
    categories.value = catRes.data;

    if (!isNew.value) {
      const res = await getQuestionPaper(id.value);
      const paper = res.data;
      form.value.title = paper.title;
      form.value.type = paper.type;
      form.value.year = paper.year ?? "";
      form.value.categoryId = paper.categoryId ?? "";
      form.value.description = paper.description ?? "";
      form.value.isPublished = paper.isPublished;
      currentFile.value = paper.fileName
        ? { name: paper.fileName, type: paper.fileType, size: paper.fileSize }
        : null;
    }
  } catch (e) {
    toast.error("Load failed", e instanceof Error ? e.message : "Unable to load question paper.");
  } finally {
    loading.value = false;
  }
}

async function save() {
  if (!validateAll(form.value)) return;

  if (form.value.type === "past_year" && !form.value.year) {
    toast.error("Validation", "Year is required for past year papers.");
    return;
  }
  if (form.value.type === "topical" && !form.value.categoryId) {
    toast.error("Validation", "Topic is required for topical papers.");
    return;
  }
  if (isNew.value && !file.value) {
    toast.error("File required", "Please select a PDF file to upload.");
    return;
  }

  saving.value = true;
  try {
    if (isNew.value) {
      const formData = new FormData();
      formData.append("title", form.value.title);
      formData.append("type", form.value.type);
      if (form.value.type === "past_year" && form.value.year) {
        formData.append("year", String(form.value.year));
      }
      if (form.value.type === "topical" && form.value.categoryId) {
        formData.append("category_id", String(form.value.categoryId));
      }
      if (form.value.description) formData.append("description", form.value.description);
      formData.append("is_published", form.value.isPublished ? "1" : "0");
      formData.append("file", file.value!);
      await createQuestionPaper(formData);
      toast.success("Question paper created");
    } else {
      const payload: Record<string, unknown> = {
        title: form.value.title,
        description: form.value.description || null,
        isPublished: form.value.isPublished,
      };
      if (form.value.type === "past_year") {
        payload.year = form.value.year || null;
        payload.categoryId = null;
      }
      if (form.value.type === "topical") {
        payload.categoryId = form.value.categoryId || null;
        payload.year = null;
      }
      await updateQuestionPaper(id.value, payload);

      if (file.value) {
        await uploadQuestionPaperFile(id.value, file.value);
      }

      toast.success("Question paper updated");
    }
    resetDirty();
    router.push("/admin/questions");
  } catch (e) {
    toast.error("Save failed", e instanceof Error ? e.message : "Unable to save question paper.");
  } finally {
    saving.value = false;
  }
}

onMounted(load);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-4xl space-y-6">
      <div class="admin-hero">
        <div class="space-y-2">
          <p class="page-kicker">Question Bank</p>
          <h1 class="page-title">{{ isNew ? 'Upload Question Paper' : 'Edit Question Paper' }}</h1>
          <p class="page-subtitle">
            {{ isNew ? 'Upload a PDF question paper and assign it as past year or topical.' : 'Update question paper details, replace file, or change publication status.' }}
          </p>
        </div>
        <div class="admin-actions">
          <router-link to="/admin/questions" class="admin-secondary-button">
            <ArrowLeft class="h-4 w-4" />
            Questions
          </router-link>
        </div>
      </div>

      <div v-if="loading" class="py-12">
        <LoadingSkeleton variant="form" />
      </div>

      <template v-else>
        <div class="grid gap-4 lg:grid-cols-[1fr_280px]">
          <!-- Left column -->
          <div class="space-y-4">
            <!-- Paper details -->
            <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
              <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
                <HelpCircle class="h-4 w-4 text-indigo-600" />
                <h2 class="text-sm font-semibold text-slate-900">Paper Details</h2>
              </div>
              <div class="space-y-3 p-4">
                <FormField label="Title" required :error="errors.title">
                  <input
                    v-model="form.title"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                    placeholder="e.g. SPM 2023 Physics Paper 1"
                    @input="() => clearError('title')"
                  />
                </FormField>

                <FormField label="Type" required helpText="Past year papers organized by year; topical by subject">
                  <select
                    v-model="form.type"
                    :disabled="!isNew"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500"
                  >
                    <option value="past_year">Past Year</option>
                    <option value="topical">Topical</option>
                  </select>
                  <p v-if="!isNew" class="mt-1 text-xs text-slate-400">Type cannot be changed after creation.</p>
                </FormField>

                <FormField v-if="form.type === 'past_year'" label="Year" required>
                  <input
                    v-model.number="form.year"
                    type="number"
                    min="1990"
                    max="2099"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                    placeholder="e.g. 2023"
                  />
                </FormField>

                <FormField v-if="form.type === 'topical'" label="Topic" required>
                  <select
                    v-model="form.categoryId"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                  >
                    <option value="">Select a topic...</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                  </select>
                </FormField>

                <FormField label="Description" helpText="Optional context for students">
                  <textarea
                    v-model="form.description"
                    rows="3"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                    placeholder="Optional description of this question paper..."
                  />
                </FormField>
              </div>
            </article>

            <!-- File upload -->
            <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
              <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
                <Upload class="h-4 w-4 text-amber-600" />
                <h2 class="text-sm font-semibold text-slate-900">{{ isNew ? 'File Upload' : 'Replace File' }}</h2>
              </div>
              <div class="p-4">
                <FileDropZone
                  v-model="file"
                  accept=".pdf"
                  label="PDF files only, up to 20MB"
                  :currentFile="currentFile"
                />
              </div>
            </article>
          </div>

          <!-- Right column -->
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

        <!-- Actions -->
        <div class="flex items-center gap-3">
          <button
            class="flex items-center gap-2 rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 disabled:opacity-50"
            :disabled="saving || !form.title"
            @click="save"
          >
            <Save class="h-4 w-4" />
            {{ saving ? 'Saving...' : (isNew ? 'Upload Paper' : 'Update Paper') }}
          </button>
          <router-link
            to="/admin/questions"
            class="flex items-center gap-2 rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition-colors hover:bg-slate-50"
          >
            Cancel
          </router-link>
        </div>
      </template>
    </div>
  </AdminLayout>
</template>
