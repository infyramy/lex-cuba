<script setup lang="ts">
import { type HTMLAttributes } from "vue";
import { SelectScrollDownButton, type SelectScrollDownButtonProps } from "radix-vue";
import { ChevronDown } from "lucide-vue-next";
import { cn } from "@/lib/utils";

interface Props extends SelectScrollDownButtonProps {
  class?: HTMLAttributes["class"];
}
const props = defineProps<Props>();
</script>
<template>
  <SelectScrollDownButton :class="cn('flex cursor-default items-center justify-center py-1', props.class)">
    <ChevronDown class="h-4 w-4" />
  </SelectScrollDownButton>
</template>
