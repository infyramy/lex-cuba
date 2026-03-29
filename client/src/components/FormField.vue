<script setup lang="ts">
defineProps<{
  label: string;
  required?: boolean;
  helpText?: string;
  error?: string;
  htmlFor?: string;
}>();
</script>

<template>
  <div :class="error ? 'field-error' : ''">
    <label v-if="label" :for="htmlFor" class="mb-1 block text-xs font-medium text-slate-700">
      {{ label }}
      <span v-if="required" class="text-rose-500">*</span>
    </label>
    <slot />
    <p v-if="error" class="mt-1 text-xs text-rose-500">{{ error }}</p>
    <p v-else-if="helpText" class="mt-1 text-xs text-slate-400">{{ helpText }}</p>
  </div>
</template>
