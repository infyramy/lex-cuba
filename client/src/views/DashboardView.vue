<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import {
  Users, UserCheck, CreditCard, AlertTriangle,
  FileText, Scale, HelpCircle, Link, ArrowRight,
  TrendingUp, Star, Activity, Clock,
} from "lucide-vue-next";
import AdminLayout from "@/layouts/AdminLayout.vue";
import { fetchDashboardSummary } from "@/api/cms";
import { useToast } from "@/composables/useToast";
import type { ChartItem, DashboardSummary } from "@/types";

const router = useRouter();
const toast  = useToast();
const loading = ref(true);

const counts = ref<DashboardSummary["counts"]>({
  admins: 0, members: 0, activeMembers: 0, suspendedMembers: 0,
  activeSubscriptions: 0, notes: 0,
  caseSummaries: 0, questions: 0, freeLinks: 0, packages: 0,
});
const metrics = ref<DashboardSummary["metrics"]>({
  conversionRate: 0, activationRate: 0, expiringSoon: 0,
  popularPackage: null, popularPkgCount: 0,
});
const charts = ref<DashboardSummary["charts"]>({
  memberStatus: [], subPipeline: [],
});
const recentMembers = ref<DashboardSummary["recent"]["members"]>([]);

// ── Donut helpers ──────────────────────────────────────────────────────────
const R = 44; const CX = 60; const CY = 60; const SW = 13;
const CIRC = 2 * Math.PI * R;
function donutSegs(items: ChartItem[]) {
  const total = items.reduce((s, i) => s + i.value, 0);
  let cum = 0;
  return items.map(item => {
    const f = total > 0 ? item.value / total : 0;
    const seg = { ...item, dash: CIRC * f + " " + CIRC * (1 - f), offset: -CIRC * cum, pct: Math.round(f * 100) };
    cum += f;
    return seg;
  });
}
const memberSegs  = computed(() => donutSegs(charts.value.memberStatus));
const memberTotal = computed(() => charts.value.memberStatus.reduce((s, i) => s + i.value, 0));
const pipeSegs    = computed(() => donutSegs(charts.value.subPipeline));
const pipeTotal   = computed(() => charts.value.subPipeline.reduce((s, i) => s + i.value, 0));

// ── Gauge helper (for rates) ───────────────────────────────────────────────
// A half-circle gauge rendered as an arc SVG
function gaugePath(pct: number, radius = 40) {
  const clamped = Math.min(Math.max(pct, 0), 100);
  const angle = (clamped / 100) * 180;
  const rad   = (angle - 180) * (Math.PI / 180);
  const x     = 60 + radius * Math.cos(rad);
  const y     = 60 + radius * Math.sin(rad);
  // sweep=0 (counterclockwise) draws the top half of the circle within the viewBox
  return `M ${60 - radius} 60 A ${radius} ${radius} 0 0 0 ${x.toFixed(2)} ${y.toFixed(2)}`;
}

function formatDate(d: string) {
  return new Date(d).toLocaleDateString("en-MY", { day: "numeric", month: "short" });
}
function statusColor(s: string) {
  return s === "active" ? "bg-emerald-100 text-emerald-700" : "bg-rose-100 text-rose-600";
}

onMounted(async () => {
  try {
    const res          = await fetchDashboardSummary();
    counts.value       = res.data.counts;
    metrics.value      = res.data.metrics;
    charts.value       = res.data.charts;
    recentMembers.value = res.data.recent.members ?? [];
  } catch (e) {
    toast.error("Load failed", e instanceof Error ? e.message : "Unable to load dashboard.");
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">

      <!-- Hero -->
      <div class="admin-hero">
        <div>
          <p class="page-kicker">Operations Centre</p>
          <h1 class="page-title">Dashboard</h1>
        </div>
        <span class="admin-pill bg-slate-100 text-slate-500 text-xs">Live data</span>
      </div>

      <div v-if="loading" class="py-20 text-center text-sm text-slate-400">Loading…</div>

      <template v-else>

        <!-- ══ ROW 1 — KPI Strip ══ -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">

          <!-- Total Members -->
          <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm">
            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50">
              <Users class="h-4 w-4 text-blue-600" />
            </div>
            <div class="min-w-0">
              <p class="text-2xl font-bold leading-none text-slate-900">{{ counts.members }}</p>
              <p class="mt-0.5 text-xs font-medium text-slate-500">Total Members</p>
              <p class="mt-1 truncate text-xs text-slate-400">
                {{ counts.activeMembers }} active &middot; {{ counts.suspendedMembers }} suspended
              </p>
            </div>
          </div>

          <!-- Active Subscribers -->
          <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm">
            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-50">
              <CreditCard class="h-4 w-4 text-violet-600" />
            </div>
            <div class="min-w-0">
              <p class="text-2xl font-bold leading-none text-slate-900">{{ counts.activeSubscriptions }}</p>
              <p class="mt-0.5 text-xs font-medium text-slate-500">Active Subscribers</p>
              <p class="mt-1 truncate text-xs text-slate-400">of {{ counts.members }} members</p>
            </div>
          </div>

          <!-- Conversion Rate -->
          <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm">
            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-purple-50">
              <TrendingUp class="h-4 w-4 text-purple-600" />
            </div>
            <div class="min-w-0">
              <div class="flex items-baseline gap-1.5">
                <p class="text-2xl font-bold leading-none text-slate-900">{{ metrics.conversionRate }}%</p>
                <span
                  class="inline-block h-2 rounded-full"
                  :style="{ width: Math.max(metrics.conversionRate * 0.36, 4) + 'px', backgroundColor: '#7c3aed' }"
                />
              </div>
              <p class="mt-0.5 text-xs font-medium text-slate-500">Conversion Rate</p>
              <p class="mt-1 text-xs text-slate-400">Members with a subscription</p>
            </div>
          </div>

          <!-- Activation Rate -->
          <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm">
            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50">
              <Activity class="h-4 w-4 text-emerald-600" />
            </div>
            <div class="min-w-0">
              <div class="flex items-baseline gap-1.5">
                <p class="text-2xl font-bold leading-none text-slate-900">{{ metrics.activationRate }}%</p>
                <span
                  class="inline-block h-2 rounded-full"
                  :style="{ width: Math.max(metrics.activationRate * 0.36, 4) + 'px', backgroundColor: '#10b981' }"
                />
              </div>
              <p class="mt-0.5 text-xs font-medium text-slate-500">Activation Rate</p>
              <p class="mt-1 text-xs text-slate-400">Active out of total members</p>
            </div>
          </div>

          <!-- Expiring in 30 Days -->
          <div
            class="flex items-start gap-3 rounded-xl border px-4 py-3.5 shadow-sm"
            :class="metrics.expiringSoon > 0 ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-white'"
          >
            <div
              class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
              :class="metrics.expiringSoon > 0 ? 'bg-amber-100' : 'bg-slate-50'"
            >
              <AlertTriangle
                class="h-4 w-4"
                :class="metrics.expiringSoon > 0 ? 'text-amber-600' : 'text-slate-400'"
              />
            </div>
            <div class="min-w-0">
              <p
                class="text-2xl font-bold leading-none"
                :class="metrics.expiringSoon > 0 ? 'text-red-600' : 'text-slate-400'"
              >{{ metrics.expiringSoon }}</p>
              <p class="mt-0.5 text-xs font-medium" :class="metrics.expiringSoon > 0 ? 'text-amber-700' : 'text-slate-500'">
                Expiring in 30 Days
              </p>
              <p class="mt-1 text-xs" :class="metrics.expiringSoon > 0 ? 'text-amber-600' : 'text-slate-400'">
                {{ metrics.expiringSoon === 0 ? 'None expiring soon' : 'subscriptions to renew' }}
              </p>
            </div>
          </div>

        </div>

        <!-- ══ ROW 2 — Charts ══ -->
        <div class="grid gap-4 lg:grid-cols-2">

          <!-- Member status donut -->
          <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
              <h2 class="text-sm font-semibold text-slate-800">Member Breakdown</h2>
              <button class="text-xs text-slate-400 hover:text-slate-600" @click="router.push('/admin/members')">
                View all →
              </button>
            </div>
            <div class="flex items-center gap-5">
              <div class="relative shrink-0">
                <svg width="120" height="120" viewBox="0 0 120 120">
                  <circle :cx="CX" :cy="CY" :r="R" fill="none" stroke="#f1f5f9" :stroke-width="SW" />
                  <circle v-for="seg in memberSegs" :key="seg.label" :cx="CX" :cy="CY" :r="R"
                    fill="none" :stroke="seg.color" :stroke-width="SW"
                    :stroke-dasharray="seg.dash" :stroke-dashoffset="seg.offset"
                    stroke-linecap="butt" style="transition:stroke-dasharray .5s" transform="rotate(-90 60 60)" />
                </svg>
                <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                  <span class="text-xl font-bold text-slate-900">{{ memberTotal }}</span>
                  <span class="text-xs text-slate-400">total</span>
                </div>
              </div>
              <div class="flex-1 space-y-2.5">
                <div v-for="seg in memberSegs" :key="seg.label" class="flex items-center gap-2">
                  <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: seg.color }" />
                  <span class="text-xs text-slate-600">{{ seg.label }}</span>
                  <span class="ml-auto text-xs font-semibold text-slate-800">{{ seg.value }}</span>
                  <span class="w-8 text-right text-xs text-slate-400">{{ seg.pct }}%</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Subscription pipeline donut -->
          <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
              <h2 class="text-sm font-semibold text-slate-800">Subscription Pipeline</h2>
              <button class="text-xs text-slate-400 hover:text-slate-600" @click="router.push('/admin/members')">
                View all →
              </button>
            </div>
            <div class="flex items-center gap-5">
              <div class="relative shrink-0">
                <svg width="120" height="120" viewBox="0 0 120 120">
                  <circle :cx="CX" :cy="CY" :r="R" fill="none" stroke="#f1f5f9" :stroke-width="SW" />
                  <circle v-for="seg in pipeSegs" :key="seg.label" :cx="CX" :cy="CY" :r="R"
                    fill="none" :stroke="seg.color" :stroke-width="SW"
                    :stroke-dasharray="seg.dash" :stroke-dashoffset="seg.offset"
                    stroke-linecap="butt" style="transition:stroke-dasharray .5s" transform="rotate(-90 60 60)" />
                </svg>
                <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                  <span class="text-xl font-bold text-slate-900">{{ pipeTotal }}</span>
                  <span class="text-xs text-slate-400">total</span>
                </div>
              </div>
              <div class="flex-1 space-y-2.5">
                <div v-for="seg in pipeSegs" :key="seg.label" class="flex items-center gap-2">
                  <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: seg.color }" />
                  <span class="text-xs text-slate-600">{{ seg.label }}</span>
                  <span class="ml-auto text-xs font-semibold text-slate-800">{{ seg.value }}</span>
                  <span class="w-8 text-right text-xs text-slate-400">{{ seg.pct }}%</span>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- ══ ROW 3 — Activity + Insights ══ -->
        <div class="grid gap-4 lg:grid-cols-3">

          <!-- Recent Members mini-table (2/3 width) -->
          <div class="rounded-xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
              <div class="flex items-center gap-2.5">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100">
                  <Users class="h-3.5 w-3.5 text-slate-600" />
                </div>
                <div>
                  <h2 class="text-sm font-semibold text-slate-900">Recent Members</h2>
                  <p class="text-xs text-slate-400">Newest registrations</p>
                </div>
              </div>
              <button class="admin-secondary-button" @click="router.push('/admin/members')">
                View all <ArrowRight class="h-3.5 w-3.5" />
              </button>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="border-b border-slate-100 text-left">
                    <th class="px-5 py-2 text-xs font-semibold text-slate-500">Name</th>
                    <th class="px-5 py-2 text-xs font-semibold text-slate-500">Email</th>
                    <th class="px-5 py-2 text-xs font-semibold text-slate-500">Status</th>
                    <th class="px-5 py-2 text-xs font-semibold text-slate-500">Joined</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="m in recentMembers" :key="m.id"
                    class="cursor-pointer transition-colors hover:bg-slate-50"
                    @click="router.push('/admin/members/' + m.id)"
                  >
                    <td class="px-5 py-2.5 font-medium text-slate-900">{{ m.name }}</td>
                    <td class="px-5 py-2.5 text-xs text-slate-500">{{ m.email }}</td>
                    <td class="px-5 py-2.5">
                      <span class="admin-pill text-xs" :class="statusColor(m.status)">{{ m.status }}</span>
                    </td>
                    <td class="px-5 py-2.5 text-xs text-slate-400">{{ formatDate(m.createdAt) }}</td>
                  </tr>
                  <tr v-if="recentMembers.length === 0">
                    <td colspan="4" class="px-5 py-8 text-center text-sm text-slate-400">No members yet.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Insights panel (1/3 width) -->
          <div class="flex flex-col gap-3">

            <!-- Top Plan -->
            <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm">
              <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-50">
                <Star class="h-4 w-4 text-amber-500" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-500">Top Plan</p>
                <p class="mt-0.5 truncate text-base font-bold text-slate-900">
                  {{ metrics.popularPackage ?? '—' }}
                </p>
                <p class="mt-0.5 text-xs text-slate-400">
                  {{ metrics.popularPkgCount > 0 ? metrics.popularPkgCount + ' subscribers' : 'No subscriptions yet' }}
                </p>
              </div>
            </div>

            <!-- Suspended Members -->
            <div
              class="flex cursor-pointer items-start gap-3 rounded-xl border px-4 py-3.5 shadow-sm transition-colors hover:bg-rose-50"
              :class="counts.suspendedMembers > 0 ? 'border-rose-200 bg-rose-50' : 'border-slate-200 bg-white'"
              @click="router.push('/admin/members')"
            >
              <div
                class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                :class="counts.suspendedMembers > 0 ? 'bg-rose-100' : 'bg-slate-50'"
              >
                <UserCheck
                  class="h-4 w-4"
                  :class="counts.suspendedMembers > 0 ? 'text-rose-500' : 'text-slate-400'"
                />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-semibold" :class="counts.suspendedMembers > 0 ? 'text-rose-700' : 'text-slate-500'">
                  Suspended Members
                </p>
                <p class="mt-0.5 text-2xl font-bold leading-none" :class="counts.suspendedMembers > 0 ? 'text-rose-600' : 'text-slate-400'">
                  {{ counts.suspendedMembers }}
                </p>
                <p class="mt-0.5 text-xs" :class="counts.suspendedMembers > 0 ? 'text-rose-400' : 'text-slate-400'">
                  {{ counts.suspendedMembers > 0 ? 'tap to review' : 'none suspended' }}
                </p>
              </div>
            </div>

          </div>
        </div>

        <!-- ══ ROW 4 — Module Quick Links ══ -->
        <div class="rounded-xl border border-slate-200 bg-white px-5 py-3.5 shadow-sm">
          <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Quick Access</p>
          <div class="flex flex-wrap gap-2">

            <button
              v-for="chip in [
                { key: 'notes',               label: 'Notes',           icon: FileText,      color: 'text-indigo-600', bg: 'bg-indigo-50 hover:bg-indigo-100',  border: 'border-indigo-200', route: '/admin/notes' },
                { key: 'caseSummaries',       label: 'Case Summaries',  icon: Scale,         color: 'text-amber-600',  bg: 'bg-amber-50 hover:bg-amber-100',    border: 'border-amber-200',  route: '/admin/case-summaries' },
                { key: 'questions',           label: 'Questions',       icon: HelpCircle,    color: 'text-teal-600',   bg: 'bg-teal-50 hover:bg-teal-100',      border: 'border-teal-200',   route: '/admin/questions' },
                { key: 'freeLinks',           label: 'Case Law',        icon: Link,          color: 'text-slate-600',  bg: 'bg-slate-50 hover:bg-slate-100',    border: 'border-slate-200',  route: '/admin/free-links' },
                { key: 'packages',            label: 'Plans',           icon: TrendingUp,    color: 'text-rose-600',   bg: 'bg-rose-50 hover:bg-rose-100',      border: 'border-rose-200',   route: '/admin/packages' },
                { key: 'members',             label: 'Members',         icon: Users,         color: 'text-blue-600',   bg: 'bg-blue-50 hover:bg-blue-100',      border: 'border-blue-200',   route: '/admin/members' },
                { key: 'admins',              label: 'Admins',          icon: UserCheck,     color: 'text-purple-600', bg: 'bg-purple-50 hover:bg-purple-100',  border: 'border-purple-200', route: '/admin/admins' },
              ]" :key="chip.key"
              class="flex cursor-pointer items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-medium transition-colors"
              :class="[chip.bg, chip.border, chip.color]"
              @click="router.push(chip.route)"
            >
              <component :is="chip.icon" class="h-3 w-3" />
              <span>{{ chip.label }}</span>
              <span class="ml-0.5 font-bold">{{ counts[chip.key as keyof typeof counts] }}</span>
            </button>

          </div>
        </div>

      </template>
    </div>
  </AdminLayout>
</template>
