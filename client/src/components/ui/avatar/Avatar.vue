<script setup lang="ts">
import { type HTMLAttributes, computed } from "vue";
import { AvatarRoot, type AvatarRootProps, useForwardProps } from "radix-vue";
import { cn } from "@/lib/utils";

interface Props extends AvatarRootProps {
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
  <AvatarRoot
    v-bind="forwardedProps"
    :class="cn('relative flex h-10 w-10 shrink-0 overflow-hidden rounded-full', props.class)"
  >
    <slot />
  </AvatarRoot>
</template>
