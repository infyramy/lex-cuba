import { computed, onBeforeUnmount, onMounted, ref, watch, type Ref } from "vue";

export function useFormDirty<T extends Record<string, unknown>>(form: Ref<T>) {
  const snapshot = ref<string>("");

  function takeSnapshot() {
    snapshot.value = JSON.stringify(form.value);
  }

  const isDirty = computed(() => {
    if (!snapshot.value) return false;
    return JSON.stringify(form.value) !== snapshot.value;
  });

  function resetDirty() {
    takeSnapshot();
  }

  function onBeforeUnload(e: BeforeUnloadEvent) {
    if (isDirty.value) {
      e.preventDefault();
    }
  }

  onMounted(() => {
    window.addEventListener("beforeunload", onBeforeUnload);
  });

  onBeforeUnmount(() => {
    window.removeEventListener("beforeunload", onBeforeUnload);
  });

  // Take initial snapshot after a tick (to allow form to be populated)
  watch(form, takeSnapshot, { once: true, deep: true });

  return { isDirty, resetDirty };
}
