<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
  BookOpen,
  Save,
  ArrowLeft,
  Upload,
  File,
  Link,
} from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import FormField from "@/components/FormField.vue";
import FileDropZone from "@/components/FileDropZone.vue";
import LoadingSkeleton from "@/components/LoadingSkeleton.vue";
import { Switch } from "@/components/ui/switch";
import {
  getStatute,
  createStatute,
  updateStatute,
} from "@/api/cms";
import { useToast } from "@/composables/useToast";
import { useFormDirty } from "@/composables/useFormDirty";
import { useFieldValidation } from "@/composables/useFieldValidation";
import type { StatuteType } from "@/types";

const route = useRoute();
const router = useRouter();
const toast = useToast();

const isNew = computed(() => route.params.id === "new" || !route.params.id);
const id = computed(() => (isNew.value ? 0 : Number(route.params.id)));

const loading = ref(false);
const saving = ref(false);

const form = ref({
  title: "",
  type: "link" as StatuteType,
  url: "",
  description: "",
  isPublished: false,
  sortOrder: 0 as number | "",
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
    if (!isNew.value) {
      const res = await getStatute(id.value);
      const statute = res.data;
      form.value.title = statute.title;
      form.value.type = statute.type;
      form.value.url = statute.url ?? "";
      form.value.description = statute.description ?? "";
      form.value.isPublished = statute.isPublished;
      form.value.sortOrder = statute.sortOrder ?? 0;
      if (statute.fileName) {
        currentFile.value = {
          name: statute.fileName,
          type: statute.fileType ?? "",
          size: statute.fileSize ?? 0,
        };
      }
    }
  } catch (e) {
    toast.error("Load failed", e instanceof Error ? e.message : "Unable to load statute.");
  } finally {
    loading.value = false;
  }
}

async function save() {
  if (!validateAll(form.value)) return;

  if (form.value.type === "link" && !form.value.url.trim()) {
    toast.error("Validation", "URL is required for link type statutes.");
    return;
  }
  if (isNew.value && form.value.type === "document" && !file.value) {
    toast.error("File required", "Please select a file to upload.");
    return;
  }

  saving.value = true;
  try {
    if (isNew.value) {
      if (form.value.type === "document") {
        const formData = new FormData();
        formData.append("title", form.value.title);
        formData.append("type", form.value.type);
        if (form.value.description) formData.append("description", form.value.description);
        formData.append("is_published", form.value.isPublished ? "1" : "0");
        if (form.value.sortOrder !== "") formData.append("sort_order", String(form.value.sortOrder));
        formData.append("file", file.value!);
        await createStatute(formData);
      } else {
        await createStatute({
          title: form.value.title,
          type: form.value.type,
          url: form.value.url,
          description: form.value.description || undefined,
          isPublished: form.value.isPublished,
          sortOrder: form.value.sortOrder !== "" ? Number(form.value.sortOrder) : undefined,
        });
      }
      toast.success("Statute created");
    } else {
      if (file.value) {
        const formData = new FormData();
        formData.append("title", form.value.title);
        formData.append("type", form.value.type);
        if (form.value.type === "link") formData.append("url", form.value.url);
        if (form.value.description) formData.append("description", form.value.description);
        formData.append("is_published", form.value.isPublished ? "1" : "0");
        if (form.value.sortOrder !== "") formData.append("sort_order", String(form.value.sortOrder));
        formData.append("file", file.value);
        await updateStatute(id.value, formData);
      } else {
        const payload: Record<string, unknown> = {
          title: form.value.title,
          type: form.value.type,
          description: form.value.description || null,
          isPublished: form.value.isPublished,
          sortOrder: form.value.sortOrder !== "" ? Number(form.value.sortOrder) : 0,
        };
        if (form.value.type === "link") {
          payload.url = form.value.url;
        }
        await updateStatute(id.value, payload);
      }
      toast.success("Statute updated");
    }
    resetDirty();
    router.push("/admin/statutes");
  } catch (e) {
    toast.error("Save failed", e instanceof Error ? e.message : "Unable to save statute.");
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
          <p class="page-kicker">Statutes</p>
          <h1 class="page-title">{{ isNew ? 'Create Statute' : 'Edit Statute' }}</h1>
          <p class="page-subtitle">
            {{ isNew ? 'Add a new statute as a link or document upload.' : 'Update statute details or replace the uploaded file.' }}
          </p>
        </div>
        <div class="admin-actions">
          <router-link to="/admin/statutes" class="admin-secondary-button">
            <ArrowLeft class="h-4 w-4" />
            Statutes
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
            <!-- Statute details -->
            <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
              <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
                <BookOpen class="h-4 w-4 text-indigo-600" />
                <h2 class="text-sm font-semibold text-slate-900">Statute Details</h2>
              </div>
              <div class="space-y-3 p-4">
                <FormField label="Title" required :error="errors.title">
                  <input
                    v-model="form.title"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                    placeholder="e.g. Evidence Act 1950"
                    @input="() => clearError('title')"
                  />
                </FormField>

                <FormField label="Type" required>
                  <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input
                        type="radio"
                        v-model="form.type"
                        value="link"
                        :disabled="!isNew"
                        class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500"
                      />
                      <Link class="h-4 w-4 text-slate-500" />
                      <span class="text-sm text-slate-700">Link</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input
                        type="radio"
                        v-model="form.type"
                        value="document"
                        :disabled="!isNew"
                        class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500"
                      />
                      <File class="h-4 w-4 text-slate-500" />
                      <span class="text-sm text-slate-700">Document</span>
                    </label>
                  </div>
                  <p v-if="!isNew" class="mt-1 text-xs text-slate-400">Type cannot be changed after creation.</p>
                </FormField>

                <!-- URL field (link type) -->
                <FormField v-if="form.type === 'link'" label="URL" required helpText="Full URL including https://">
                  <input
                    v-model="form.url"
                    type="url"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                    placeholder="https://example.com/statute.pdf"
                  />
                </FormField>

                <FormField label="Description">
                  <textarea
                    v-model="form.description"
                    rows="3"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                    placeholder="Optional description of this statute..."
                  />
                </FormField>

                <FormField label="Sort Order" helpText="Lower numbers appear first in the list">
                  <input
                    v-model.number="form.sortOrder"
                    type="number"
                    min="0"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                    placeholder="0"
                  />
                </FormField>
              </div>
            </article>

            <!-- File upload (document type only) -->
            <article v-if="form.type === 'document'" class="rounded-lg border border-slate-200 bg-white shadow-sm">
              <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
                <Upload class="h-4 w-4 text-amber-600" />
                <h2 class="text-sm font-semibold text-slate-900">{{ isNew ? 'File Upload' : 'Replace File' }}</h2>
              </div>
              <div class="p-4">
                <FileDropZone
                  v-model="file"
                  accept=".pdf,.doc,.docx"
                  label="PDF or Word documents, up to 20MB"
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
            {{ saving ? 'Saving...' : (isNew ? 'Create Statute' : 'Update Statute') }}
          </button>
          <router-link
            to="/admin/statutes"
            class="flex items-center gap-2 rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition-colors hover:bg-slate-50"
          >
            Cancel
          </router-link>
        </div>
      </template>
    </div>
  </AdminLayout>
</template>
