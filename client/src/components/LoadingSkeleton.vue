<script setup lang="ts">
defineProps<{
  variant?: "form" | "table" | "card";
  lines?: number;
}>();
</script>

<template>
  <div class="animate-pulse space-y-4">
    <!-- Form variant -->
    <template v-if="variant === 'form' || !variant">
      <div v-for="i in (lines ?? 4)" :key="i" class="space-y-2">
        <div class="h-3 w-24 rounded bg-slate-200" />
        <div class="h-9 rounded-lg bg-slate-200" />
      </div>
    </template>

    <!-- Table variant -->
    <template v-else-if="variant === 'table'">
      <div class="h-8 rounded bg-slate-200" />
      <div v-for="i in (lines ?? 4)" :key="i" class="h-12 rounded bg-slate-100" />
    </template>

    <!-- Card variant -->
    <template v-else-if="variant === 'card'">
      <div class="rounded-lg border border-slate-200 p-5 space-y-3">
        <div class="h-4 w-32 rounded bg-slate-200" />
        <div class="h-3 w-full rounded bg-slate-100" />
        <div class="h-3 w-3/4 rounded bg-slate-100" />
      </div>
    </template>
  </div>
</template>
