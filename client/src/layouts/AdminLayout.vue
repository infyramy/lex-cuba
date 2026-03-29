<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
  ChevronDown,
  LogOut,
  Menu,
  Shield,
  User,
  X,
} from "lucide-vue-next";

import type { MenuItemDef, MenuNode } from "@/config/admin-menu";
import { useToast } from "@/composables/useToast";

import { useAuthStore } from "@/stores/auth";
import { useMenuStore } from "@/stores/menu";
import { useSiteStore } from "@/stores/site";
import { API_BASE_URL } from "@/env";

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const menuStore = useMenuStore();
const site = useSiteStore();
const toast = useToast();

const mobileNavOpen = ref(false);
const userMenuOpen = ref(false);
const userMenuRef = ref<HTMLElement | null>(null);

const handleEscape = (event: KeyboardEvent) => {
  if (event.key !== "Escape") return;
  mobileNavOpen.value = false;
  userMenuOpen.value = false;
};

function handleClickOutside(e: MouseEvent) {
  if (userMenuRef.value && !userMenuRef.value.contains(e.target as Node)) {
    userMenuOpen.value = false;
  }
}

function resolveUrl(url: string) {
  if (!url) return "";
  if (url.startsWith("http")) return url;
  return `${API_BASE_URL}${url}`;
}

onMounted(() => {
  site.load();
  menuStore.load();
  document.addEventListener("keydown", handleEscape);
  document.addEventListener("click", handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener("keydown", handleEscape);
  document.removeEventListener("click", handleClickOutside);
});

const openMenus = reactive<Record<string, boolean>>({});

const currentPageTitle = computed(() => {
  return typeof route.meta.title === "string" ? route.meta.title : "Dashboard";
});

const userInitials = computed(() => {
  if (!auth.user?.name) return "A";
  return auth.user.name
    .split(" ")
    .map((n) => n[0])
    .join("")
    .toUpperCase()
    .slice(0, 2);
});

const userRoleLabel = computed(() => auth.user?.role || "Administrator");

const rowBaseClass = "gap-3 px-3.5 py-2 text-[13px] leading-tight";

const childRowClass = "block rounded-xl px-3 py-1.5 text-[13px] leading-tight transition-colors";

async function signOut() {
  userMenuOpen.value = false;
  try {
    await auth.signOut();
    toast.success("Signed out", "You have been logged out.");
    router.push("/admin/login");
  } catch (e) {
    toast.error("Sign out failed", e instanceof Error ? e.message : "Please try again.");
  }
}

function isActive(path: string): boolean {
  if (path === "/admin") return route.path === "/admin";
  return route.path.startsWith(path);
}

function itemClass(path: string) {
  if (isActive(path)) {
    return "border border-slate-200 bg-white text-slate-950 shadow-sm";
  }
  return "border border-transparent text-slate-700 hover:bg-white/80 hover:text-slate-950";
}

function childClass(path: string) {
  if (route.path === path) {
    return "bg-slate-900 text-white shadow-sm";
  }
  return "text-slate-600 hover:bg-slate-100 hover:text-slate-900";
}

function toggleMenu(id: string) {
  openMenus[id] = !openMenus[id];
}

function isNodeActive(node: { to: string; children?: MenuNode[] }): boolean {
  if (isActive(node.to)) return true;
  if (!node.children || node.children.length === 0) return false;
  return node.children.some((child) => isNodeActive(child));
}

function syncOpenMenus() {
  const syncNode = (node: MenuNode | MenuItemDef) => {
    if (node.children && node.children.length > 0 && isNodeActive(node)) {
      openMenus[node.id] = true;
      for (const child of node.children) syncNode(child);
    }
  };

  for (const group of menuStore.resolvedMenu) {
    for (const item of group.items) {
      syncNode(item);
    }
  }
}

watch(() => route.path, syncOpenMenus, { immediate: true });
watch(() => route.fullPath, () => {
  mobileNavOpen.value = false;
});
watch(() => menuStore.resolvedMenu, syncOpenMenus, { deep: true });
</script>

<template>
  <div class="admin-shell">
    <header class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/90 backdrop-blur">
      <div class="mx-auto flex h-[72px] max-w-[1680px] items-center gap-3 px-4 sm:px-6">
        <button
          class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm transition-colors hover:bg-slate-50 md:hidden"
          aria-label="Open navigation"
          @click="mobileNavOpen = true"
        >
          <Menu class="h-5 w-5" />
        </button>

        <div class="flex min-w-0 items-center gap-3">
          <div v-if="site.siteIconUrl" class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <img :src="resolveUrl(site.siteIconUrl)" alt="Site icon" class="h-full w-full object-contain" />
          </div>
          <div
            v-else
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl text-white"
            :style="{ background: 'linear-gradient(180deg, var(--accent-600), var(--accent-700))' }"
          >
            <Shield class="h-4 w-4" />
          </div>

          <div class="min-w-0">
            <p class="page-kicker">Administrative Portal</p>
            <div class="flex min-w-0 items-center gap-2">
              <p class="truncate text-sm font-semibold text-slate-950 sm:text-base">
                {{ site.siteTitle || "CORRAD" }}
              </p>
              <span class="hidden text-slate-300 sm:inline">/</span>
              <p class="hidden truncate text-sm text-slate-500 sm:inline">
                {{ currentPageTitle }}
              </p>
            </div>
          </div>
        </div>

        <div class="ml-auto flex min-w-0 items-center gap-2">
          <!-- User dropdown -->
          <div ref="userMenuRef" class="relative">
            <!-- Desktop trigger -->
            <button
              class="hidden min-w-0 items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm transition-colors hover:bg-slate-50 sm:flex"
              @click="userMenuOpen = !userMenuOpen"
            >
              <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white"
                :style="{ background: 'linear-gradient(180deg, var(--accent-600), var(--accent-700))' }"
              >
                {{ userInitials }}
              </div>
              <div class="min-w-0 leading-tight">
                <p class="truncate text-sm font-medium text-slate-900">{{ auth.user?.name || "Administrator" }}</p>
                <p class="truncate text-xs text-slate-500">{{ userRoleLabel }}</p>
              </div>
              <ChevronDown
                class="h-3.5 w-3.5 shrink-0 text-slate-400 transition-transform duration-200"
                :class="userMenuOpen ? 'rotate-180' : ''"
              />
            </button>

            <!-- Mobile trigger (avatar only) -->
            <button
              class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-xs font-semibold text-white shadow-sm transition-colors hover:opacity-90 sm:hidden"
              :style="{ background: 'linear-gradient(180deg, var(--accent-600), var(--accent-700))' }"
              @click="userMenuOpen = !userMenuOpen"
            >
              {{ userInitials }}
            </button>

            <!-- Dropdown panel -->
            <Transition
              enter-active-class="transition duration-150 ease-out"
              enter-from-class="opacity-0 scale-95"
              enter-to-class="opacity-100 scale-100"
              leave-active-class="transition duration-100 ease-in"
              leave-from-class="opacity-100 scale-100"
              leave-to-class="opacity-0 scale-95"
            >
              <div
                v-if="userMenuOpen"
                class="absolute right-0 top-full z-50 mt-1.5 w-52 origin-top-right overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
              >
                <div class="border-b border-slate-100 px-3 py-2.5">
                  <p class="truncate text-xs font-semibold text-slate-900">{{ auth.user?.name || "Administrator" }}</p>
                  <p class="truncate text-xs text-slate-400">{{ userRoleLabel }}</p>
                </div>
                <div class="p-1">
                  <router-link
                    :to="'/admin/users/' + auth.user?.id"
                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 transition-colors hover:bg-slate-100"
                    @click="userMenuOpen = false"
                  >
                    <User class="h-3.5 w-3.5 text-slate-400" />
                    Edit Profile
                  </router-link>
                  <button
                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-rose-600 transition-colors hover:bg-rose-50"
                    @click="signOut"
                  >
                    <LogOut class="h-3.5 w-3.5" />
                    Sign Out
                  </button>
                </div>
              </div>
            </Transition>
          </div>
        </div>
      </div>
    </header>

    <div class="mx-auto flex max-w-[1680px] gap-5 px-4 pb-6 pt-5 sm:px-6">
      <Transition
        enter-active-class="transition-opacity duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="mobileNavOpen" class="fixed inset-0 z-30 bg-slate-950/35 md:hidden" @click="mobileNavOpen = false" />
      </Transition>

      <aside
        class="fixed inset-y-[72px] left-0 z-40 flex w-[18.5rem] flex-col overflow-hidden border-r border-slate-200/80 bg-white transition-transform duration-300 ease-out md:sticky md:top-5 md:z-10 md:max-h-[calc(100vh-7rem)] md:w-72 md:self-start md:rounded-3xl md:border md:shadow-[var(--shell-shadow-soft)]"
        :class="mobileNavOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
      >
        <!-- Mobile header with close button -->
        <div class="flex items-center justify-between border-b border-slate-100 px-3 py-2.5 md:hidden">
          <p class="text-xs font-semibold text-slate-500">Navigation</p>
          <button
            class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100"
            @click="mobileNavOpen = false"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <nav class="flex-1 overflow-y-auto p-3 pt-3">
          <div v-for="(group, gi) in menuStore.resolvedMenu" :key="group.id">
            <p
              v-if="group.label"
              class="px-3 text-[11px] font-medium text-slate-400"
              :class="gi === 0 ? 'mb-2' : 'mb-2 mt-5'"
            >
              {{ group.label }}
            </p>

            <div v-for="item in group.items" :key="item.id" class="mb-1">
              <button
                v-if="item.children && item.children.length > 0"
                type="button"
                class="group relative flex w-full items-center rounded-2xl font-medium transition-colors"
                :class="[
                  rowBaseClass,
                  itemClass(isNodeActive(item) ? route.path : item.to),
                ]"
                @click="toggleMenu(item.id)"
              >
                <component
                  :is="item.icon"
                  class="shrink-0 transition-colors"
                  :class="[
                    'h-4 w-4',
                    isNodeActive(item) ? 'text-slate-900' : 'text-slate-400 group-hover:text-slate-700',
                  ]"
                />
                <span class="flex-1">{{ item.label }}</span>
                <ChevronDown
                  class="h-4 w-4 text-slate-400 transition-transform duration-200"
                  :class="{ '-rotate-90': !openMenus[item.id] }"
                />
              </button>

              <router-link
                v-else
                :to="item.to"
                class="group relative flex items-center rounded-2xl font-medium transition-colors"
                :class="[
                  rowBaseClass,
                  itemClass(item.to),
                ]"
              >
                <component
                  :is="item.icon"
                  class="shrink-0 transition-colors"
                  :class="[
                    'h-4 w-4',
                    isActive(item.to) ? 'text-slate-900' : 'text-slate-400 group-hover:text-slate-700',
                  ]"
                />
                <span class="flex-1">{{ item.label }}</span>
              </router-link>

              <div
                v-if="item.children && item.children.length > 0 && openMenus[item.id]"
                class="ml-5 mt-1.5 space-y-1 border-l border-slate-200 pl-4"
              >
                <template v-for="child in item.children" :key="child.id">
                  <button
                    v-if="child.children && child.children.length > 0"
                    type="button"
                    class="flex w-full items-center rounded-xl text-left transition-colors"
                    :class="[childRowClass, childClass(isNodeActive(child) ? route.path : child.to)]"
                    @click="toggleMenu(child.id)"
                  >
                    <span class="flex-1">{{ child.label }}</span>
                    <ChevronDown
                      class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200"
                      :class="{ '-rotate-90': !openMenus[child.id] }"
                    />
                  </button>

                  <router-link v-else :to="child.to" :class="[childRowClass, childClass(child.to)]">
                    {{ child.label }}
                  </router-link>

                  <div
                    v-if="child.children && child.children.length > 0 && openMenus[child.id]"
                    class="ml-4 mt-1 space-y-1 border-l border-slate-200 pl-3"
                  >
                    <router-link
                      v-for="grandchild in child.children"
                      :key="grandchild.id"
                      :to="grandchild.to"
                      :class="[childRowClass, childClass(grandchild.to)]"
                    >
                      {{ grandchild.label }}
                    </router-link>
                  </div>
                </template>
              </div>
            </div>
          </div>
        </nav>

        <div v-if="site.footerText" class="border-t border-slate-200/80 bg-slate-50/70 p-3">
          <p class="text-[11px] leading-relaxed text-slate-400">
            {{ site.footerText }}
          </p>
        </div>
      </aside>

      <main class="min-w-0 flex-1">
        <div class="admin-page">
          <Transition name="page" mode="out-in">
            <div :key="route.path">
              <slot />
            </div>
          </Transition>
        </div>
      </main>
    </div>
  </div>
</template>

<style scoped>
.page-enter-active,
.page-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.page-enter-from {
  opacity: 0;
  transform: translateY(5px);
}
.page-leave-to {
  opacity: 0;
  transform: translateY(-3px);
}
</style>
