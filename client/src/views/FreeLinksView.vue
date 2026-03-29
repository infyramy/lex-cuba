<script setup lang="ts">
import { onMounted, ref } from "vue";
import {
  Link,
  Plus,
  Pencil,
  Trash2,
  CheckCircle2,
  XCircle,
  ExternalLink,
  Save,
  X,
  GripVertical,
} from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import FormField from "@/components/FormField.vue";
import LoadingSkeleton from "@/components/LoadingSkeleton.vue";
import EmptyState from "@/components/EmptyState.vue";
import { Switch } from "@/components/ui/switch";
import { listFreeLinks, createFreeLink, updateFreeLink, deleteFreeLink } from "@/api/cms";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import { useToast } from "@/composables/useToast";
import type { FreeLink } from "@/types";

const toast = useToast();
const confirmDialog = useConfirmDialog();

const rows = ref<FreeLink[]>([]);
const loading = ref(true);

// Drag-and-drop state
const draggedIdx = ref<number | null>(null);

function dragStart(idx: number) {
  draggedIdx.value = idx;
}

function dragOver(e: DragEvent) {
  e.preventDefault();
}

async function drop(targetIdx: number) {
  if (draggedIdx.value === null || draggedIdx.value === targetIdx) {
    draggedIdx.value = null;
    return;
  }
  const arr = [...rows.value];
  const [moved] = arr.splice(draggedIdx.value, 1);
  arr.splice(targetIdx, 0, moved);
  rows.value = arr;
  draggedIdx.value = null;
  // Persist new sort orders
  try {
    await Promise.all(
      arr.map((item, i) => updateFreeLink(item.id, { title: item.title, url: item.url, isActive: item.isActive, sortOrder: i + 1 }))
    );
  } catch (e) {
    toast.error("Reorder failed", e instanceof Error ? e.message : "Unable to save order.");
    await load();
  }
}

// Dialog state
const dialogOpen = ref(false);
const dialogMode = ref<"create" | "edit">("create");
const dialogSaving = ref(false);
const editId = ref(0);
const formTitle = ref("");
const formUrl = ref("");
const formIsActive = ref(true);

function openCreate() {
  dialogMode.value = "create";
  formTitle.value = "";
  formUrl.value = "";
  formIsActive.value = true;
  editId.value = 0;
  dialogOpen.value = true;
}

function openEdit(item: FreeLink) {
  dialogMode.value = "edit";
  formTitle.value = item.title;
  formUrl.value = item.url;
  formIsActive.value = item.isActive;
  editId.value = item.id;
  dialogOpen.value = true;
}

function closeDialog() {
  dialogOpen.value = false;
}

async function load() {
  loading.value = true;
  try {
    const response = await listFreeLinks("?limit=100");
    rows.value = response.data;
  } catch (e) {
    toast.error("Load failed", e instanceof Error ? e.message : "Unable to load free links.");
  } finally {
    loading.value = false;
  }
}

async function saveDialog() {
  if (!formTitle.value || !formUrl.value) {
    toast.error("Validation", "Title and URL are required.");
    return;
  }
  dialogSaving.value = true;
  try {
    const payload = {
      title: formTitle.value,
      url: formUrl.value,
      sortOrder: dialogMode.value === "create" ? rows.value.length + 1 : (rows.value.find(r => r.id === editId.value)?.sortOrder ?? 0),
      isActive: formIsActive.value,
    };
    if (dialogMode.value === "create") {
      await createFreeLink(payload);
      toast.success("Link created");
    } else {
      await updateFreeLink(editId.value, payload);
      toast.success("Link updated");
    }
    closeDialog();
    await load();
  } catch (e) {
    toast.error("Save failed", e instanceof Error ? e.message : "Unable to save link.");
  } finally {
    dialogSaving.value = false;
  }
}

async function remove(id: number) {
  const allowed = await confirmDialog.confirm({
    title: "Delete link?",
    message: "This action cannot be undone.",
    confirmText: "Delete",
    destructive: true,
  });
  if (!allowed) return;
  try {
    await deleteFreeLink(id);
    await load();
    toast.success("Link deleted");
  } catch (e) {
    toast.error("Delete failed", e instanceof Error ? e.message : "Unable to delete link.");
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
          <h1 class="page-title">Case Law</h1>
          <p class="page-subtitle">Manage case law reference links for the mobile app library. Add, reorder, or deactivate entries as needed.</p>
        </div>
        <div class="admin-actions">
          <button class="admin-primary-button" @click="openCreate">
            <Plus class="h-4 w-4" />
            Add Link
          </button>
        </div>
      </div>

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-2.5 border-b border-slate-100 px-4 py-3">
          <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50">
            <Link class="h-3.5 w-3.5 text-emerald-600" />
          </div>
          <h2 class="text-sm font-semibold text-slate-900">All Links</h2>
          <span class="admin-pill bg-slate-100 text-slate-500">{{ rows.length }}</span>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="w-8 px-2 py-2"></th>
                <th class="px-4 py-2 text-xs font-semibold text-slate-500">Title</th>
                <th class="px-4 py-2 text-xs font-semibold text-slate-500">URL</th>
                <th class="px-4 py-2 text-xs font-semibold text-slate-500">Active</th>
                <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="loading">
                <td colspan="5"><LoadingSkeleton variant="table" :lines="3" /></td>
              </tr>
              <tr v-else-if="rows.length === 0">
                <td colspan="5">
                  <EmptyState :icon="Link" title="No links yet" description="Add your first case law directory link" action-label="Add Link" @action="openCreate" />
                </td>
              </tr>
              <tr
                v-for="(item, idx) in rows"
                v-else
                :key="item.id"
                draggable="true"
                class="cursor-grab transition-colors hover:bg-slate-50 active:cursor-grabbing"
                :class="draggedIdx === idx ? 'opacity-40' : ''"
                @dragstart="dragStart(idx)"
                @dragover="dragOver"
                @drop="drop(idx)"
              >
                <td class="px-2 py-2 text-slate-300">
                  <GripVertical class="h-4 w-4" />
                </td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.title }}</td>
                <td class="max-w-[300px] truncate px-4 py-2">
                  <a :href="item.url" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800">
                    {{ item.url }}
                    <ExternalLink class="h-3 w-3" />
                  </a>
                </td>
                <td class="px-4 py-2">
                  <span v-if="item.isActive" class="admin-pill bg-emerald-100 text-emerald-700">
                    <CheckCircle2 class="h-3 w-3" /> Active
                  </span>
                  <span v-else class="admin-pill bg-slate-100 text-slate-500">
                    <XCircle class="h-3 w-3" /> Inactive
                  </span>
                </td>
                <td class="px-4 py-2 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button
                      class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                      @click="openEdit(item)"
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

    <!-- ═══════ ADD/EDIT DIALOG ═══════ -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="dialogOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" @click.self="closeDialog">
          <div class="mx-4 w-full max-w-lg rounded-xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
              <div class="flex items-center gap-2">
                <Link class="h-4 w-4 text-emerald-600" />
                <h3 class="text-sm font-semibold text-slate-900">{{ dialogMode === 'create' ? 'Add Link' : 'Edit Link' }}</h3>
              </div>
              <button class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600" @click="closeDialog">
                <X class="h-4 w-4" />
              </button>
            </div>
            <div class="space-y-3 p-5">
              <FormField label="Title" :required="true">
                <input
                  v-model="formTitle"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                  placeholder="Link title"
                />
              </FormField>
              <FormField label="URL" :required="true" help-text="Full URL including https://">
                <input
                  v-model="formUrl"
                  type="url"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                  placeholder="https://example.com"
                />
              </FormField>
              <div>
                <label class="flex items-center gap-3">
                  <Switch :checked="formIsActive" @update:checked="formIsActive = $event" />
                  <span class="text-sm text-slate-700">Active</span>
                </label>
              </div>
            </div>
            <div class="flex items-center justify-end gap-3 border-t border-slate-100 px-5 py-3">
              <button
                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50"
                @click="closeDialog"
              >
                Cancel
              </button>
              <button
                class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-slate-800 disabled:opacity-50"
                :disabled="dialogSaving || !formTitle || !formUrl"
                @click="saveDialog"
              >
                <Save class="h-3.5 w-3.5" />
                {{ dialogSaving ? 'Saving...' : (dialogMode === 'create' ? 'Create' : 'Update') }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </AdminLayout>
</template>
