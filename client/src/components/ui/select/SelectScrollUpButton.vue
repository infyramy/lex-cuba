<script setup lang="ts">
import { type HTMLAttributes } from "vue";
import { SelectScrollUpButton, type SelectScrollUpButtonProps } from "radix-vue";
import { ChevronUp } from "lucide-vue-next";
import { cn } from "@/lib/utils";

interface Props extends SelectScrollUpButtonProps {
  class?: HTMLAttributes["class"];
}
const props = defineProps<Props>();
</script>
<template>
  <SelectScrollUpButton :class="cn('flex cursor-default items-center justify-center py-1', props.class)">
    <ChevronUp class="h-4 w-4" />
  </SelectScrollUpButton>
</template>
