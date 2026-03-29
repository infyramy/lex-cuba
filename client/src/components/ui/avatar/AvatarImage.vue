<script setup lang="ts">
import { type HTMLAttributes, computed } from "vue";
import { AvatarImage, type AvatarImageProps, useForwardProps } from "radix-vue";
import { cn } from "@/lib/utils";

interface Props extends AvatarImageProps {
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
  <AvatarImage v-bind="forwardedProps" :class="cn('aspect-square h-full w-full', props.class)" />
</template>
