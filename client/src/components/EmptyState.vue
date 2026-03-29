<script setup lang="ts">
import { type Component } from "vue";
import { Inbox } from "lucide-vue-next";

defineProps<{
  icon?: Component;
  title: string;
  description?: string;
  actionLabel?: string;
  actionTo?: string;
}>();

defineEmits<{
  action: [];
}>();
</script>

<template>
  <div class="flex flex-col items-center justify-center py-12 text-center">
    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
      <component :is="icon || Inbox" class="h-5 w-5 text-slate-400" />
    </div>
    <p class="text-sm font-medium text-slate-600">{{ title }}</p>
    <p v-if="description" class="mt-1 max-w-xs text-xs text-slate-400">{{ description }}</p>
    <router-link v-if="actionLabel && actionTo" :to="actionTo" class="mt-3 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-800">
      {{ actionLabel }}
    </router-link>
    <button v-else-if="actionLabel" class="mt-3 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-800" @click="$emit('action')">
      {{ actionLabel }}
    </button>
  </div>
</template>
