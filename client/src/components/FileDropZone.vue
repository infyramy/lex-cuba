<script setup lang="ts">
import { ref } from "vue";
import { Upload, FileText } from "lucide-vue-next";

const props = defineProps<{
  accept?: string;
  label?: string;
  currentFile?: { name: string; type: string; size: number } | null;
}>();

const model = defineModel<File | null>();

const dragOver = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

function formatFileSize(bytes: number): string {
  if (bytes === 0) return "0 B";
  const k = 1024;
  const sizes = ["B", "KB", "MB", "GB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + " " + sizes[i];
}

function onDragOver(e: DragEvent) {
  e.preventDefault();
  dragOver.value = true;
}

function onDragLeave() {
  dragOver.value = false;
}

function onDrop(e: DragEvent) {
  e.preventDefault();
  dragOver.value = false;
  const file = e.dataTransfer?.files[0];
  if (file) model.value = file;
}

function onFileSelect(e: Event) {
  const input = e.target as HTMLInputElement;
  const file = input.files?.[0];
  if (file) model.value = file;
}

function browse() {
  fileInput.value?.click();
}
</script>

<template>
  <div>
    <!-- Current file preview -->
    <div v-if="currentFile || model" class="mb-3 flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
      <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white border border-slate-200">
        <FileText class="h-4 w-4 text-slate-500" />
      </div>
      <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-medium text-slate-700">{{ model?.name || currentFile?.name || "Current file" }}</p>
        <p class="text-xs text-slate-400">
          {{ model ? formatFileSize(model.size) : currentFile?.size ? formatFileSize(currentFile.size) : "" }}
          <span v-if="model" class="text-emerald-500 font-medium ml-1">New file selected</span>
        </p>
      </div>
    </div>

    <!-- Drop zone -->
    <div
      class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed p-6 text-center transition-colors cursor-pointer"
      :class="dragOver ? 'border-violet-400 bg-violet-50' : 'border-slate-300 bg-slate-50 hover:border-slate-400 hover:bg-slate-100'"
      @dragover="onDragOver"
      @dragleave="onDragLeave"
      @drop="onDrop"
      @click="browse"
    >
      <Upload class="mb-2 h-6 w-6 text-slate-400" />
      <p class="text-sm text-slate-600">
        <span class="font-medium text-violet-600">Click to browse</span> or drag and drop
      </p>
      <p v-if="label" class="mt-1 text-xs text-slate-400">{{ label }}</p>
    </div>
    <input
      ref="fileInput"
      type="file"
      :accept="accept"
      class="sr-only"
      @change="onFileSelect"
    />
  </div>
</template>
