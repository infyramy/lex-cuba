<script setup lang="ts">
import { type HTMLAttributes, computed } from "vue";
import { DropdownMenuItem, type DropdownMenuItemProps, useForwardProps } from "radix-vue";
import { cn } from "@/lib/utils";

interface Props extends DropdownMenuItemProps {
  class?: HTMLAttributes["class"];
  inset?: boolean;
}
const props = defineProps<Props>();
const delegatedProps = computed(() => {
  const { class: _, inset: _i, ...d } = props;
  return d;
});
const forwardedProps = useForwardProps(delegatedProps);
</script>
<template>
  <DropdownMenuItem
    v-bind="forwardedProps"
    :class="
      cn(
        'relative flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none transition-colors focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
        inset && 'pl-8',
        props.class,
      )
    "
  >
    <slot />
  </DropdownMenuItem>
</template>
