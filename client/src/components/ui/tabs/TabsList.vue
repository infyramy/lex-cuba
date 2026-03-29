<script setup lang="ts">
import { type HTMLAttributes, computed } from "vue";
import { TabsList, type TabsListProps, useForwardProps } from "radix-vue";
import { cn } from "@/lib/utils";

interface Props extends TabsListProps {
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
  <TabsList
    v-bind="forwardedProps"
    :class="cn('inline-flex h-10 items-center justify-center rounded-md bg-muted p-1 text-muted-foreground', props.class)"
  >
    <slot />
  </TabsList>
</template>
