<script setup lang="ts">
import { type HTMLAttributes, computed } from "vue";
import { AvatarFallback, type AvatarFallbackProps, useForwardProps } from "radix-vue";
import { cn } from "@/lib/utils";

interface Props extends AvatarFallbackProps {
  class?: HTMLAttributes["class"];
}
const props = defineProps<Props>();
const delegatedProps = computed(() => {
  const { class: _, ...d } = props;
  return d;
});
const forwardedProps = useForwardProps(delegatedProps);
</script>
<template>
  <AvatarFallback
    v-bind="forwardedProps"
    :class="cn('flex h-full w-full items-center justify-center rounded-full bg-muted', props.class)"
  >
    <slot />
  </AvatarFallback>
</template>
