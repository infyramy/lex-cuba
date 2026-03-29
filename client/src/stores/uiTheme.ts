import { ref, watch } from "vue";
import { defineStore } from "pinia";

import type { ThemeColor } from "@/types";

const COLOR_KEY = "admin.theme.color";

export const useUiThemeStore = defineStore("ui-theme", () => {
  const themeColor = ref<ThemeColor>("grey");

  function applyToDocument() {
    if (typeof document === "undefined") return;
    const root = document.documentElement;
    root.dataset.themeColor = themeColor.value;
    root.classList.remove("dark");
  }

  function persist() {
    if (typeof window === "undefined") return;
    localStorage.setItem(COLOR_KEY, themeColor.value);
  }

  function initFromStorage() {
    if (typeof window === "undefined") return;
    themeColor.value = "grey";
    persist();
    applyToDocument();
  }

  function setThemeColor(_color: ThemeColor) {
    themeColor.value = "grey";
  }

  watch(themeColor, () => {
    persist();
    applyToDocument();
  }, { immediate: true });

  return {
    themeColor,
    initFromStorage,
    setThemeColor,
  };
});
