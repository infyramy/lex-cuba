<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { Shield, ArrowRight, AlertCircle, Eye, EyeOff } from "lucide-vue-next";

import { useAuthStore } from "@/stores/auth";
import { useSiteStore } from "@/stores/site";
import { API_BASE_URL } from "@/env";

const router = useRouter();
const auth = useAuthStore();
const site = useSiteStore();

const email = ref("");
const password = ref("");
const error = ref("");
const showPassword = ref(false);

function resolveUrl(url: string) {
  if (!url) return "";
  if (url.startsWith("http")) return url;
  return `${API_BASE_URL}${url}`;
}

onMounted(() => {
  if (!site.initialized) site.load();
});

async function submit() {
  error.value = "";
  try {
    await auth.signIn(email.value, password.value);
    router.push("/admin");
  } catch (e) {
    error.value = e instanceof Error ? e.message : "Login failed";
  }
}
</script>

<template>
  <div class="admin-login-shell flex min-h-screen flex-col items-center justify-center px-4 py-10">
    <div class="w-full max-w-[440px]">
      <div class="mb-8 flex justify-center">
        <div v-if="site.siteIconUrl" class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          <img :src="resolveUrl(site.siteIconUrl)" alt="Site logo" class="h-full w-full object-contain p-2" />
        </div>
        <div
          v-else
          class="flex h-14 w-14 items-center justify-center rounded-2xl text-white shadow-sm"
          :style="{ background: 'linear-gradient(180deg, var(--accent-600), var(--accent-700))' }"
        >
          <Shield class="h-6 w-6 text-white" />
        </div>
      </div>

      <div class="rounded-[1.75rem] border border-slate-200 bg-white px-8 pb-8 pt-7 shadow-[var(--shell-shadow)] sm:px-10 sm:pb-10 sm:pt-8">
        <div class="mb-8 text-center">
          <p class="page-kicker">Administrative Access</p>
          <h1 class="mt-3 text-[2rem] font-semibold tracking-tight text-slate-950">Secure sign in</h1>
          <p class="mt-2 text-sm leading-6 text-slate-500">{{ site.siteTitle || 'Admin' }} control centre</p>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-slate-800">Email</label>
            <input
              v-model="email"
              type="email"
              class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-900 shadow-sm transition-all placeholder:text-slate-400 focus:border-slate-500 focus:outline-none focus:ring-4 focus:ring-slate-200/70"
              placeholder="you@example.com"
            />
          </div>

          <div class="space-y-1.5">
            <label class="text-sm font-medium text-slate-800">Password</label>
            <div class="relative">
              <input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 pr-10 text-sm text-slate-900 shadow-sm transition-all placeholder:text-slate-400 focus:border-slate-500 focus:outline-none focus:ring-4 focus:ring-slate-200/70"
                placeholder="Enter your password"
              />
              <button
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition-colors hover:text-slate-600"
                @click="showPassword = !showPassword"
              >
                <EyeOff v-if="showPassword" class="h-4 w-4" />
                <Eye v-else class="h-4 w-4" />
              </button>
            </div>
          </div>

          <div v-if="error" class="flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-3 text-sm text-rose-700">
            <AlertCircle class="h-4 w-4 shrink-0" />
            {{ error }}
          </div>

          <button
            type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-sm transition-all hover:bg-slate-800 disabled:opacity-60"
            :disabled="auth.loading"
          >
            {{ auth.loading ? 'Signing in...' : 'Continue' }}
            <ArrowRight v-if="!auth.loading" class="h-4 w-4" />
          </button>
        </form>
      </div>

      <p class="mt-8 text-center text-xs text-slate-500">&copy; {{ new Date().getFullYear() }} {{ site.siteTitle || 'Admin' }}</p>
    </div>
  </div>
</template>
