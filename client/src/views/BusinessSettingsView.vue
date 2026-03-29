<script setup lang="ts">
import { onMounted, ref } from "vue";
import {
  Settings,
  Palette,
  Globe,
  Smartphone,
  Save,
  CheckCircle2,
  Upload,
  X,
} from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import FormField from "@/components/FormField.vue";
import { getSettings, updateSettings, uploadMedia } from "@/api/cms";
import { useToast } from "@/composables/useToast";

const toast = useToast();

type TabKey = "general" | "branding" | "contact" | "mobile";
const activeTab = ref<TabKey>("general");

const loading = ref(true);
const error = ref("");

// Per-tab saving state
const savingGeneral = ref(false);
const savedGeneral = ref(false);
const savingBranding = ref(false);
const savedBranding = ref(false);
const savingContact = ref(false);
const savedContact = ref(false);
const savingMobile = ref(false);
const savedMobile = ref(false);

const form = ref({
  // General
  siteTitle: "",
  tagline: "",
  language: "en",
  timezone: "Asia/Kuala_Lumpur",
  footerText: "",
  maintenanceMode: false,
  // Branding
  companyName: "",
  companyAddress: "",
  brandColor: "#5469d4",
  siteIconUrl: "",
  sidebarLogoUrl: "",
  faviconUrl: "",
  aboutContent: "",
  // Contact & Links
  supportEmail: "",
  supportPhone: "",
  websiteUrl: "",
  facebookUrl: "",
  instagramUrl: "",
  twitterUrl: "",
  linkedinUrl: "",
  termsUrl: "",
  privacyUrl: "",
  // Mobile App
  appName: "",
  upgradePromptMessage: "",
  minimumAppVersion: "",
  appStoreUrl: "",
  googlePlayUrl: "",
});

const timezoneOptions = [
  "Asia/Kuala_Lumpur",
  "Asia/Singapore",
  "Asia/Jakarta",
  "Asia/Tokyo",
  "Asia/Shanghai",
  "Asia/Kolkata",
  "Asia/Dubai",
  "Europe/London",
  "Europe/Paris",
  "Europe/Berlin",
  "America/New_York",
  "America/Chicago",
  "America/Los_Angeles",
  "Australia/Sydney",
  "Pacific/Auckland",
];

async function load() {
  loading.value = true;
  try {
    const res = await getSettings();
    const d = res.data;
    form.value = {
      siteTitle: d.siteTitle || "",
      tagline: d.tagline || "",
      language: d.language || "en",
      timezone: d.timezone || "Asia/Kuala_Lumpur",
      footerText: d.footerText || "",
      maintenanceMode: d.maintenanceMode === "true" || d.maintenanceMode === true,
      companyName: d.companyName || "",
      companyAddress: d.companyAddress || "",
      brandColor: d.brandColor || "#5469d4",
      siteIconUrl: d.siteIconUrl || "",
      sidebarLogoUrl: d.sidebarLogoUrl || "",
      faviconUrl: d.faviconUrl || "",
      aboutContent: d.aboutContent || "",
      supportEmail: d.supportEmail || "",
      supportPhone: d.supportPhone || "",
      websiteUrl: d.websiteUrl || "",
      facebookUrl: d.facebookUrl || "",
      instagramUrl: d.instagramUrl || "",
      twitterUrl: d.twitterUrl || "",
      linkedinUrl: d.linkedinUrl || "",
      termsUrl: d.termsUrl || "",
      privacyUrl: d.privacyUrl || "",
      appName: d.appName || "",
      upgradePromptMessage: d.upgradePromptMessage || "",
      minimumAppVersion: d.minimumAppVersion || "",
      appStoreUrl: d.appStoreUrl || "",
      googlePlayUrl: d.googlePlayUrl || "",
    };
  } catch (e) {
    error.value = e instanceof Error ? e.message : "Failed to load settings";
    toast.error("Load failed", error.value);
  } finally {
    loading.value = false;
  }
}

async function saveTab(tab: TabKey) {
  let payload: Record<string, unknown> = {};
  let savingRef: typeof savingGeneral;
  let savedRef: typeof savedGeneral;

  switch (tab) {
    case "general":
      savingRef = savingGeneral;
      savedRef = savedGeneral;
      payload = {
        siteTitle: form.value.siteTitle,
        tagline: form.value.tagline,
        language: form.value.language,
        timezone: form.value.timezone,
        footerText: form.value.footerText,
        maintenanceMode: form.value.maintenanceMode ? "true" : "false",
      };
      break;
    case "branding":
      savingRef = savingBranding;
      savedRef = savedBranding;
      payload = {
        companyName: form.value.companyName,
        companyAddress: form.value.companyAddress,
        brandColor: form.value.brandColor,
        siteIconUrl: form.value.siteIconUrl,
        sidebarLogoUrl: form.value.sidebarLogoUrl,
        faviconUrl: form.value.faviconUrl,
        aboutContent: form.value.aboutContent,
      };
      break;
    case "contact":
      savingRef = savingContact;
      savedRef = savedContact;
      payload = {
        supportEmail: form.value.supportEmail,
        supportPhone: form.value.supportPhone,
        websiteUrl: form.value.websiteUrl,
        facebookUrl: form.value.facebookUrl,
        instagramUrl: form.value.instagramUrl,
        twitterUrl: form.value.twitterUrl,
        linkedinUrl: form.value.linkedinUrl,
        termsUrl: form.value.termsUrl,
        privacyUrl: form.value.privacyUrl,
      };
      break;
    case "mobile":
      savingRef = savingMobile;
      savedRef = savedMobile;
      payload = {
        appName: form.value.appName,
        upgradePromptMessage: form.value.upgradePromptMessage,
        minimumAppVersion: form.value.minimumAppVersion,
        appStoreUrl: form.value.appStoreUrl,
        googlePlayUrl: form.value.googlePlayUrl,
      };
      break;
  }

  savingRef.value = true;
  error.value = "";
  try {
    await updateSettings(payload);
    savedRef.value = true;
    toast.success("Settings saved");
    setTimeout(() => {
      savedRef.value = false;
    }, 2000);
  } catch (e) {
    error.value = e instanceof Error ? e.message : "Failed to save settings";
    toast.error("Save failed", error.value);
  } finally {
    savingRef.value = false;
  }
}

async function handleLogoUpload(event: Event, field: "siteIconUrl" | "sidebarLogoUrl" | "faviconUrl") {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file) return;
  try {
    const res = await uploadMedia(file);
    form.value[field] = res.data.url;
    toast.success("Image uploaded");
  } catch (e) {
    toast.error("Upload failed", e instanceof Error ? e.message : "Unknown error");
  }
  input.value = "";
}

onMounted(load);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-3xl space-y-6">
      <!-- Hero -->
      <div class="admin-hero">
        <div class="space-y-2">
          <p class="page-kicker">Configuration</p>
          <h1 class="page-title">Business Settings</h1>
          <p class="page-subtitle">
            Keep organisation identity, branding assets, and public-facing labels
            consistent across the admin and mobile experience.
          </p>
        </div>
      </div>

      <div v-if="loading" class="py-12 text-center text-sm text-slate-400">Loading...</div>

      <template v-else>
        <!-- Tabs -->
        <div class="admin-tablist">
          <button
            :class="activeTab === 'general' ? 'admin-tab admin-tab-active' : 'admin-tab'"
            @click="activeTab = 'general'"
          >
            <Settings class="mr-1.5 inline h-4 w-4" />
            General
          </button>
          <button
            :class="activeTab === 'branding' ? 'admin-tab admin-tab-active' : 'admin-tab'"
            @click="activeTab = 'branding'"
          >
            <Palette class="mr-1.5 inline h-4 w-4" />
            Branding
          </button>
          <button
            :class="activeTab === 'contact' ? 'admin-tab admin-tab-active' : 'admin-tab'"
            @click="activeTab = 'contact'"
          >
            <Globe class="mr-1.5 inline h-4 w-4" />
            Contact &amp; Links
          </button>
          <button
            :class="activeTab === 'mobile' ? 'admin-tab admin-tab-active' : 'admin-tab'"
            @click="activeTab = 'mobile'"
          >
            <Smartphone class="mr-1.5 inline h-4 w-4" />
            Mobile App
          </button>
        </div>

        <!-- Tab: General -->
        <article v-if="activeTab === 'general'" class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="flex items-center gap-2.5 border-b border-slate-100 px-4 py-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100">
              <Settings class="h-3.5 w-3.5 text-slate-700" />
            </div>
            <h2 class="text-sm font-semibold text-slate-900">General Settings</h2>
          </div>
          <div class="space-y-4 p-4">
            <FormField label="Site Title" required>
              <input
                v-model="form.siteTitle"
                type="text"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder="e.g. LexSZA"
              />
            </FormField>

            <FormField label="Tagline">
              <input
                v-model="form.tagline"
                type="text"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder="A short description of your site"
              />
            </FormField>

            <div class="grid gap-4 md:grid-cols-2">
              <FormField label="Language">
                <select
                  v-model="form.language"
                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                >
                  <option value="en">English</option>
                  <option value="ms">Bahasa Malaysia</option>
                </select>
              </FormField>

              <FormField label="Timezone">
                <select
                  v-model="form.timezone"
                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                >
                  <option v-for="tz in timezoneOptions" :key="tz" :value="tz">{{ tz }}</option>
                </select>
              </FormField>
            </div>

            <FormField label="Footer Text">
              <textarea
                v-model="form.footerText"
                rows="3"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder="Footer text displayed across the site"
              />
            </FormField>

            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
              <label class="flex items-start gap-3">
                <input
                  v-model="form.maintenanceMode"
                  type="checkbox"
                  class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                />
                <div>
                  <span class="text-sm font-medium text-slate-900">Maintenance Mode</span>
                  <p class="mt-0.5 text-xs text-slate-500">
                    When enabled, non-admin users see an "Under Maintenance" page.
                  </p>
                </div>
              </label>
            </div>
          </div>
          <div class="flex items-center gap-3 border-t border-slate-100 px-4 py-3">
            <button class="admin-primary-button" :disabled="savingGeneral" @click="saveTab('general')">
              <Save class="h-4 w-4" />
              {{ savingGeneral ? "Saving..." : "Save General" }}
            </button>
            <Transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="translate-y-1 opacity-0"
              enter-to-class="translate-y-0 opacity-100"
              leave-active-class="transition duration-150 ease-in"
              leave-from-class="opacity-100"
              leave-to-class="opacity-0"
            >
              <span v-if="savedGeneral" class="flex items-center gap-1.5 text-sm font-medium text-emerald-600">
                <CheckCircle2 class="h-4 w-4" />
                Saved
              </span>
            </Transition>
          </div>
        </article>

        <!-- Tab: Branding -->
        <article v-if="activeTab === 'branding'" class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="flex items-center gap-2.5 border-b border-slate-100 px-4 py-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100">
              <Palette class="h-3.5 w-3.5 text-slate-700" />
            </div>
            <h2 class="text-sm font-semibold text-slate-900">Branding</h2>
          </div>
          <div class="space-y-4 p-4">
            <FormField label="Company Name">
              <input
                v-model="form.companyName"
                type="text"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder="e.g. LexSZA Sdn Bhd"
              />
            </FormField>

            <FormField label="Company Address">
              <textarea
                v-model="form.companyAddress"
                rows="3"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder="Company address..."
              />
            </FormField>

            <FormField label="Brand Color">
              <div class="flex items-center gap-3">
                <input
                  v-model="form.brandColor"
                  type="color"
                  class="h-10 w-10 shrink-0 cursor-pointer rounded-lg border-0 p-0"
                />
                <input
                  v-model="form.brandColor"
                  type="text"
                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                  placeholder="#5469d4"
                />
                <div
                  class="h-12 w-12 shrink-0 rounded-lg border border-slate-200"
                  :style="{ backgroundColor: form.brandColor }"
                />
              </div>
            </FormField>

            <!-- Site Logo -->
            <FormField label="Site Logo" help-text="Displayed in the header and login page">
              <div class="flex items-center gap-3">
                <img
                  v-if="form.siteIconUrl"
                  :src="form.siteIconUrl"
                  alt="Site logo"
                  class="h-12 w-12 rounded-lg border border-slate-200 object-contain"
                />
                <div
                  v-else
                  class="flex h-12 w-12 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50"
                >
                  <Upload class="h-4 w-4 text-slate-400" />
                </div>
                <label class="cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50">
                  Choose File
                  <input type="file" class="hidden" accept="image/*" @change="handleLogoUpload($event, 'siteIconUrl')" />
                </label>
                <button
                  v-if="form.siteIconUrl"
                  class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                  @click="form.siteIconUrl = ''"
                >
                  <X class="h-4 w-4" />
                </button>
              </div>
            </FormField>

            <!-- Sidebar Logo -->
            <FormField label="Sidebar Logo" help-text="Shown in the admin sidebar">
              <div class="flex items-center gap-3">
                <img
                  v-if="form.sidebarLogoUrl"
                  :src="form.sidebarLogoUrl"
                  alt="Sidebar logo"
                  class="h-12 w-12 rounded-lg border border-slate-200 object-contain"
                />
                <div
                  v-else
                  class="flex h-12 w-12 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50"
                >
                  <Upload class="h-4 w-4 text-slate-400" />
                </div>
                <label class="cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50">
                  Choose File
                  <input type="file" class="hidden" accept="image/*" @change="handleLogoUpload($event, 'sidebarLogoUrl')" />
                </label>
                <button
                  v-if="form.sidebarLogoUrl"
                  class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                  @click="form.sidebarLogoUrl = ''"
                >
                  <X class="h-4 w-4" />
                </button>
              </div>
            </FormField>

            <!-- Favicon -->
            <FormField label="Favicon" help-text="Browser tab icon (recommended: 32x32 PNG or ICO)">
              <div class="flex items-center gap-3">
                <img
                  v-if="form.faviconUrl"
                  :src="form.faviconUrl"
                  alt="Favicon"
                  class="h-12 w-12 rounded-lg border border-slate-200 object-contain"
                />
                <div
                  v-else
                  class="flex h-12 w-12 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50"
                >
                  <Upload class="h-4 w-4 text-slate-400" />
                </div>
                <label class="cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50">
                  Choose File
                  <input type="file" class="hidden" accept="image/*" @change="handleLogoUpload($event, 'faviconUrl')" />
                </label>
                <button
                  v-if="form.faviconUrl"
                  class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                  @click="form.faviconUrl = ''"
                >
                  <X class="h-4 w-4" />
                </button>
              </div>
            </FormField>

            <FormField label="About Content" help-text="Displayed on the mobile app's About page. Supports plain text.">
              <textarea
                v-model="form.aboutContent"
                rows="8"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder="Write the About page content here..."
              />
            </FormField>
          </div>
          <div class="flex items-center gap-3 border-t border-slate-100 px-4 py-3">
            <button class="admin-primary-button" :disabled="savingBranding" @click="saveTab('branding')">
              <Save class="h-4 w-4" />
              {{ savingBranding ? "Saving..." : "Save Branding" }}
            </button>
            <Transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="translate-y-1 opacity-0"
              enter-to-class="translate-y-0 opacity-100"
              leave-active-class="transition duration-150 ease-in"
              leave-from-class="opacity-100"
              leave-to-class="opacity-0"
            >
              <span v-if="savedBranding" class="flex items-center gap-1.5 text-sm font-medium text-emerald-600">
                <CheckCircle2 class="h-4 w-4" />
                Saved
              </span>
            </Transition>
          </div>
        </article>

        <!-- Tab: Contact & Links -->
        <article v-if="activeTab === 'contact'" class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="flex items-center gap-2.5 border-b border-slate-100 px-4 py-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100">
              <Globe class="h-3.5 w-3.5 text-slate-700" />
            </div>
            <h2 class="text-sm font-semibold text-slate-900">Contact &amp; Links</h2>
          </div>
          <div class="space-y-4 p-4">
            <div class="grid gap-4 md:grid-cols-2">
              <FormField label="Support Email">
                <input
                  v-model="form.supportEmail"
                  type="email"
                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                  placeholder="support@example.com"
                />
              </FormField>

              <FormField label="Support Phone">
                <input
                  v-model="form.supportPhone"
                  type="text"
                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                  placeholder="+60 12-345 6789"
                />
              </FormField>
            </div>

            <FormField label="Website URL">
              <input
                v-model="form.websiteUrl"
                type="url"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder="https://example.com"
              />
            </FormField>

            <!-- Social Links -->
            <div class="border-t border-slate-100 pt-4">
              <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Social Links</h3>
              <div class="grid gap-4 md:grid-cols-2">
                <FormField label="Facebook URL">
                  <input
                    v-model="form.facebookUrl"
                    type="url"
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    placeholder="https://facebook.com/..."
                  />
                </FormField>

                <FormField label="Instagram URL">
                  <input
                    v-model="form.instagramUrl"
                    type="url"
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    placeholder="https://instagram.com/..."
                  />
                </FormField>

                <FormField label="Twitter / X URL">
                  <input
                    v-model="form.twitterUrl"
                    type="url"
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    placeholder="https://x.com/..."
                  />
                </FormField>

                <FormField label="LinkedIn URL">
                  <input
                    v-model="form.linkedinUrl"
                    type="url"
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    placeholder="https://linkedin.com/company/..."
                  />
                </FormField>
              </div>
            </div>

            <!-- Legal -->
            <div class="border-t border-slate-100 pt-4">
              <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Legal</h3>
              <div class="grid gap-4 md:grid-cols-2">
                <FormField label="Terms of Service URL">
                  <input
                    v-model="form.termsUrl"
                    type="url"
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    placeholder="https://example.com/terms"
                  />
                </FormField>

                <FormField label="Privacy Policy URL">
                  <input
                    v-model="form.privacyUrl"
                    type="url"
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    placeholder="https://example.com/privacy"
                  />
                </FormField>
              </div>
            </div>
          </div>
          <div class="flex items-center gap-3 border-t border-slate-100 px-4 py-3">
            <button class="admin-primary-button" :disabled="savingContact" @click="saveTab('contact')">
              <Save class="h-4 w-4" />
              {{ savingContact ? "Saving..." : "Save Contact & Links" }}
            </button>
            <Transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="translate-y-1 opacity-0"
              enter-to-class="translate-y-0 opacity-100"
              leave-active-class="transition duration-150 ease-in"
              leave-from-class="opacity-100"
              leave-to-class="opacity-0"
            >
              <span v-if="savedContact" class="flex items-center gap-1.5 text-sm font-medium text-emerald-600">
                <CheckCircle2 class="h-4 w-4" />
                Saved
              </span>
            </Transition>
          </div>
        </article>

        <!-- Tab: Mobile App -->
        <article v-if="activeTab === 'mobile'" class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="flex items-center gap-2.5 border-b border-slate-100 px-4 py-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100">
              <Smartphone class="h-3.5 w-3.5 text-slate-700" />
            </div>
            <h2 class="text-sm font-semibold text-slate-900">Mobile App</h2>
          </div>
          <div class="space-y-4 p-4">
            <FormField label="App Name" help-text="Name displayed in the mobile application">
              <input
                v-model="form.appName"
                type="text"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder="LexSZA"
              />
            </FormField>

            <FormField label="Upgrade Prompt Message" help-text="Shown to free-tier users to encourage subscription">
              <textarea
                v-model="form.upgradePromptMessage"
                rows="3"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder="Upgrade to a premium plan to access all study materials and features."
              />
            </FormField>

            <FormField label="Minimum App Version" help-text="Users with older versions will be prompted to update. Format: 1.0.0">
              <input
                v-model="form.minimumAppVersion"
                type="text"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder="1.0.0"
              />
            </FormField>

            <div class="grid gap-4 md:grid-cols-2">
              <FormField label="App Store URL">
                <input
                  v-model="form.appStoreUrl"
                  type="url"
                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                  placeholder="https://apps.apple.com/..."
                />
              </FormField>

              <FormField label="Google Play URL">
                <input
                  v-model="form.googlePlayUrl"
                  type="url"
                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                  placeholder="https://play.google.com/store/apps/..."
                />
              </FormField>
            </div>
          </div>
          <div class="flex items-center gap-3 border-t border-slate-100 px-4 py-3">
            <button class="admin-primary-button" :disabled="savingMobile" @click="saveTab('mobile')">
              <Save class="h-4 w-4" />
              {{ savingMobile ? "Saving..." : "Save Mobile App" }}
            </button>
            <Transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="translate-y-1 opacity-0"
              enter-to-class="translate-y-0 opacity-100"
              leave-active-class="transition duration-150 ease-in"
              leave-from-class="opacity-100"
              leave-to-class="opacity-0"
            >
              <span v-if="savedMobile" class="flex items-center gap-1.5 text-sm font-medium text-emerald-600">
                <CheckCircle2 class="h-4 w-4" />
                Saved
              </span>
            </Transition>
          </div>
        </article>

        <p v-if="error" class="text-sm text-rose-600">{{ error }}</p>
      </template>
    </div>
  </AdminLayout>
</template>
