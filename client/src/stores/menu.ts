import { defineStore } from "pinia";
import { DEFAULT_MENU, type MenuGroupDef } from "@/config/admin-menu";

export const useMenuStore = defineStore("menu", {
  state: () => ({
    initialized: false,
  }),

  getters: {
    resolvedMenu(): MenuGroupDef[] {
      return DEFAULT_MENU;
    },
  },

  actions: {
    async load() {
      this.initialized = true;
    },
  },
});
