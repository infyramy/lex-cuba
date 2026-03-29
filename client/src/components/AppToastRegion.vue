<script setup lang="ts">
import { CheckCircle2, XCircle, Info, X } from "lucide-vue-next";
import { useToast, type ToastVariant } from "@/composables/useToast";

const { toasts, remove } = useToast();

const iconMap: Record<ToastVariant, typeof CheckCircle2> = {
  success: CheckCircle2,
  error: XCircle,
  info: Info,
};

const accentMap: Record<ToastVariant, string> = {
  success: "border-l-emerald-500",
  error: "border-l-rose-500",
  info: "border-l-blue-500",
};

const iconColorMap: Record<ToastVariant, string> = {
  success: "text-emerald-500",
  error: "text-rose-500",
  info: "text-blue-500",
};
</script>

<template>
  <div class="fixed bottom-4 right-4 z-[100] flex flex-col gap-2 sm:max-w-sm max-w-[calc(100vw-2rem)]">
    <TransitionGroup
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 translate-y-3 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 translate-x-4 scale-95"
      move-class="transition-all duration-200 ease-in-out"
    >
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="flex items-start gap-3 rounded-xl border border-l-4 border-slate-200 bg-white px-4 py-3 shadow-lg"
        :class="accentMap[toast.variant]"
      >
        <component
          :is="iconMap[toast.variant]"
          class="mt-0.5 h-4 w-4 shrink-0"
          :class="iconColorMap[toast.variant]"
        />
        <div class="min-w-0 flex-1">
          <p class="text-sm font-medium text-slate-900">{{ toast.title }}</p>
          <p v-if="toast.message" class="mt-0.5 text-xs text-slate-500">{{ toast.message }}</p>
        </div>
        <button
          class="shrink-0 rounded-lg p-1 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600"
          @click="remove(toast.id)"
        >
          <X class="h-3.5 w-3.5" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>
