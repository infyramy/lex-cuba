<script setup lang="ts">
import { onMounted, ref } from "vue";
import { CreditCard, Plus, Pencil, Trash2, CheckCircle2, XCircle, Users } from "lucide-vue-next";
import AdminLayout from "@/layouts/AdminLayout.vue";
import { listPackages, deletePackage } from "@/api/cms";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import { useToast } from "@/composables/useToast";
import type { Package } from "@/types";

const packages = ref<Package[]>([]);
const confirmDialog = useConfirmDialog();
const toast = useToast();

async function load() {
  const res = await listPackages("?limit=100&sort_by=created_at&sort_dir=asc");
  packages.value = res.data;
}

async function remove(pkg: Package) {
  const ok = await confirmDialog.confirm({
    title: `Delete "${pkg.name}"?`,
    message: "If this package has subscribers, deletion will be blocked. Deactivate it instead.",
    confirmText: "Delete",
    destructive: true,
  });
  if (!ok) return;
  try {
    await deletePackage(pkg.id);
    await load();
    toast.success("Package deleted");
  } catch (e) {
    toast.error("Cannot delete", e instanceof Error ? e.message : "Package may have active subscribers.");
  }
}

function topicCount(pkg: Package): number {
  return pkg.accessibleCategoryIds?.length ?? 0;
}

// Tier colors cycle — each plan gets a distinct accent
const tierPalette = [
  { bar: "bg-violet-500", price: "text-violet-600", duration: "bg-violet-50 text-violet-700 border-violet-200", highlight: "bg-violet-50" },
  { bar: "bg-blue-500",   price: "text-blue-600",   duration: "bg-blue-50 text-blue-700 border-blue-200",     highlight: "bg-blue-50" },
  { bar: "bg-emerald-500",price: "text-emerald-600",duration: "bg-emerald-50 text-emerald-700 border-emerald-200",highlight: "bg-emerald-50" },
  { bar: "bg-amber-500",  price: "text-amber-600",  duration: "bg-amber-50 text-amber-700 border-amber-200",  highlight: "bg-amber-50" },
];
function tier(idx: number) { return tierPalette[idx % tierPalette.length]; }

onMounted(load);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-6">
      <div class="admin-hero">
        <div class="space-y-2">
          <p class="page-kicker">Subscription Services</p>
          <h1 class="page-title">Plans</h1>
          <p class="page-subtitle">Define plan benefits, review access coverage, and manage active pricing tiers.</p>
        </div>
        <div class="admin-actions">
          <router-link to="/admin/packages/new" class="admin-primary-button">
            <Plus class="h-4 w-4" />
            New Plan
          </router-link>
        </div>
      </div>

      <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <!-- Plan cards -->
        <article
          v-for="(pkg, idx) in packages"
          :key="pkg.id"
          class="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-shadow hover:shadow-md"
          :class="!pkg.isActive ? 'opacity-60' : ''"
        >
          <!-- Colored accent bar -->
          <div class="h-[3px] w-full" :class="tier(idx).bar"></div>

          <!-- Header section -->
          <div class="px-5 pt-5 pb-4">
            <!-- Top row: name + inactive chip -->
            <div class="flex items-start justify-between gap-3">
              <h3 class="text-xl font-bold text-slate-900 leading-tight">{{ pkg.name }}</h3>
              <span
                v-if="!pkg.isActive"
                class="shrink-0 rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500"
              >
                Inactive
              </span>
            </div>

            <!-- Price row -->
            <div class="mt-3 flex items-baseline gap-1">
              <span class="text-sm font-semibold text-slate-500">RM</span>
              <span class="text-4xl font-extrabold leading-none" :class="tier(idx).price">{{ pkg.price }}</span>
            </div>

            <!-- Duration chip -->
            <div class="mt-2">
              <span
                class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium"
                :class="tier(idx).duration"
              >
                / {{ pkg.durationMonths }} month{{ pkg.durationMonths !== 1 ? 's' : '' }}
              </span>
            </div>

            <!-- Description -->
            <p v-if="pkg.description" class="mt-2 text-sm text-slate-500 line-clamp-2">{{ pkg.description }}</p>
          </div>

          <!-- Divider -->
          <div class="mx-5 border-t border-slate-100"></div>

          <!-- Features section -->
          <div class="flex-1 px-5 py-4">
            <p class="mb-3 text-[10px] uppercase tracking-wider font-semibold text-slate-400">What's included</p>
            <ul class="space-y-2">
              <li class="flex items-center gap-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full" :class="topicCount(pkg) > 0 ? 'bg-emerald-50' : 'bg-slate-50'">
                  <CheckCircle2 v-if="topicCount(pkg) > 0" class="h-3.5 w-3.5 text-emerald-500" />
                  <XCircle v-else class="h-3.5 w-3.5 text-slate-300" />
                </span>
                <span class="text-sm" :class="topicCount(pkg) > 0 ? 'font-medium text-slate-800' : 'text-slate-400'">
                  {{ topicCount(pkg) }} topic{{ topicCount(pkg) !== 1 ? 's' : '' }} included
                </span>
              </li>
              <li class="flex items-center gap-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full" :class="pkg.chatbotAccess ? 'bg-emerald-50' : 'bg-slate-50'">
                  <CheckCircle2 v-if="pkg.chatbotAccess" class="h-3.5 w-3.5 text-emerald-500" />
                  <XCircle v-else class="h-3.5 w-3.5 text-slate-300" />
                </span>
                <span class="text-sm" :class="pkg.chatbotAccess ? 'font-medium text-slate-800' : 'text-slate-400'">
                  AI Chatbot
                </span>
              </li>
            </ul>
          </div>

          <!-- Footer -->
          <div class="border-t border-slate-100 px-5 py-3 flex items-center justify-between">
            <!-- Subscribers count -->
            <span class="flex items-center gap-1.5 text-xs text-slate-500">
              <Users class="h-3.5 w-3.5 shrink-0" />
              <span v-if="(pkg.subscriptionsCount ?? 0) > 0">
                {{ pkg.subscriptionsCount }} subscriber{{ pkg.subscriptionsCount !== 1 ? 's' : '' }}
              </span>
              <span v-else class="text-slate-400">No subscribers yet</span>
            </span>

            <!-- Actions -->
            <div class="flex items-center gap-1">
              <router-link
                :to="'/admin/packages/' + pkg.id"
                class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                title="Edit plan"
              >
                <Pencil class="h-3.5 w-3.5" />
              </router-link>
              <button
                class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600"
                title="Delete plan"
                @click="remove(pkg)"
              >
                <Trash2 class="h-3.5 w-3.5" />
              </button>
            </div>
          </div>
        </article>

        <!-- Empty state -->
        <div
          v-if="packages.length === 0"
          class="col-span-3 flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-white py-16 text-center"
        >
          <CreditCard class="mb-3 h-8 w-8 text-slate-300" />
          <p class="text-sm font-medium text-slate-500">No plans yet</p>
          <p class="mt-1 text-xs text-slate-400">Create your first subscription plan to get started.</p>
          <router-link to="/admin/packages/new" class="admin-primary-button mt-4">
            <Plus class="h-4 w-4" /> New Plan
          </router-link>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
