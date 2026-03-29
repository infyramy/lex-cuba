<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
  FileText,
  Save,
  ArrowLeft,
  Upload,
} from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import FormField from "@/components/FormField.vue";
import FileDropZone from "@/components/FileDropZone.vue";
import LoadingSkeleton from "@/components/LoadingSkeleton.vue";
import { Switch } from "@/components/ui/switch";
import { getNote, createNote, updateNote, uploadNoteFile, listCategories } from "@/api/cms";
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
  description: "",
  categoryId: "" as number | "",
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
      const res = await getNote(id.value);
      const note = res.data;
      form.value.title = note.title;
      form.value.description = note.description || "";
      form.value.categoryId = note.categoryId || "";
      form.value.isPublished = note.isPublished;
      currentFile.value = note.fileName
        ? { name: note.fileName, type: note.fileType, size: note.fileSize }
        : null;
    }
  } catch (e) {
    toast.error("Load failed", e instanceof Error ? e.message : "Unable to load note.");
  } finally {
    loading.value = false;
  }
}

async function save() {
  if (!validateAll(form.value)) return;

  saving.value = true;
  try {
    if (isNew.value) {
      if (!file.value) {
        toast.error("File required", "Please select a file to upload.");
        saving.value = false;
        return;
      }
      const formData = new FormData();
      formData.append("title", form.value.title);
      if (form.value.description) formData.append("description", form.value.description);
      if (form.value.categoryId) formData.append("category_id", String(form.value.categoryId));
      formData.append("is_published", form.value.isPublished ? "1" : "0");
      formData.append("file", file.value);
      await createNote(formData);
      toast.success("Note created");
    } else {
      const payload: Record<string, unknown> = {
        title: form.value.title,
        description: form.value.description || null,
        categoryId: form.value.categoryId || null,
        isPublished: form.value.isPublished,
      };
      await updateNote(id.value, payload);

      if (file.value) {
        await uploadNoteFile(id.value, file.value);
      }

      toast.success("Note updated");
    }
    resetDirty();
    router.push("/admin/notes");
  } catch (e) {
    toast.error("Save failed", e instanceof Error ? e.message : "Unable to save note.");
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
          <h1 class="page-title">{{ isNew ? 'Upload Note' : 'Edit Note' }}</h1>
          <p class="page-subtitle">Upload study materials, assign topics, and manage publication state with clearer file and metadata sections.</p>
        </div>
        <div class="admin-actions">
          <router-link to="/admin/notes" class="admin-secondary-button">
            <ArrowLeft class="h-4 w-4" />
            Notes
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
            <!-- Note Details -->
            <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
              <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
                <FileText class="h-4 w-4 text-blue-600" />
                <h2 class="text-sm font-semibold text-slate-900">Note Details</h2>
              </div>
              <div class="space-y-3 p-4">
                <FormField label="Title" required :error="errors.title">
                  <input
                    v-model="form.title"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                    placeholder="Note title"
                    @input="() => clearError('title')"
                  />
                </FormField>
                <FormField label="Description" helpText="Brief description shown to students in the mobile app">
                  <textarea
                    v-model="form.description"
                    rows="3"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                    placeholder="Brief description..."
                  />
                </FormField>
                <FormField label="Topic" helpText="Assign to a topic for student navigation">
                  <select
                    v-model="form.categoryId"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                  >
                    <option value="">No topic</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                  </select>
                </FormField>
              </div>
            </article>

            <!-- File Upload -->
            <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
              <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
                <Upload class="h-4 w-4 text-amber-600" />
                <h2 class="text-sm font-semibold text-slate-900">{{ isNew ? 'File Upload' : 'Replace File' }}</h2>
              </div>
              <div class="p-4">
                <FileDropZone
                  v-model="file"
                  accept=".pdf,.jpeg,.jpg,.png"
                  label="PDF, JPEG, PNG up to 10MB"
                  :currentFile="currentFile"
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
            :disabled="saving || !form.title"
            @click="save"
          >
            <Save class="h-4 w-4" />
            {{ saving ? 'Saving...' : (isNew ? 'Upload Note' : 'Update Note') }}
          </button>
          <router-link
            to="/admin/notes"
            class="flex items-center gap-2 rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition-colors hover:bg-slate-50"
          >
            Cancel
          </router-link>
        </div>
      </template>
    </div>
  </AdminLayout>
</template>
