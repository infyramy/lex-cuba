import { defineStore } from "pinia";
import { getSettings } from "@/api/cms";
import type { SettingsPayload } from "@/types";

export const useSiteStore = defineStore("site", {
  state: () => ({
    siteTitle: "LexSZA",
    siteIconUrl: "",
    sidebarLogoUrl: "",
    footerText: "",
    maintenanceMode: false,
    initialized: false,
  }),
  actions: {
    async load() {
      try {
        const res = await getSettings();
        const d = res.data;
        this.siteTitle = d.siteTitle || "LexSZA";
        this.siteIconUrl = d.siteIconUrl || "";
        this.sidebarLogoUrl = d.sidebarLogoUrl || "";
        this.footerText = d.footerText || "";
        this.maintenanceMode = d.maintenanceMode === "true" || d.maintenanceMode === true;
        this.initialized = true;
      } catch {
        // use defaults
      }
    },
    applyFrom(payload: SettingsPayload) {
      this.siteTitle = payload.siteTitle || "LexSZA";
      this.siteIconUrl = payload.siteIconUrl || "";
      this.sidebarLogoUrl = payload.sidebarLogoUrl || "";
      this.footerText = payload.footerText || "";
      if (payload.maintenanceMode !== undefined) {
        this.maintenanceMode = payload.maintenanceMode === "true" || payload.maintenanceMode === true;
      }
    },
    setDocumentTitle(pageTitle: string) {
      document.title = `${pageTitle} | ${this.siteTitle}`;
    },
  },
});
