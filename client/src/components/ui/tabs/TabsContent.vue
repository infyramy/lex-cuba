<script setup lang="ts">
import { type HTMLAttributes, computed } from "vue";
import { TabsContent, type TabsContentProps, useForwardProps } from "radix-vue";
import { cn } from "@/lib/utils";

interface Props extends TabsContentProps {
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
  <TabsContent
    v-bind="forwardedProps"
    :class="cn('mt-2 ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2', props.class)"
  >
    <slot />
  </TabsContent>
</template>
