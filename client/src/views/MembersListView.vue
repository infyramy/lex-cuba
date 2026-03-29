<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import {
  Users,
  Plus,
  Pencil,
  Trash2,
  CheckCircle2,
  XCircle,
  Search,
  CreditCard,
} from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import { listMembers, deleteMember } from "@/api/cms";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import { useToast } from "@/composables/useToast";
import type { MemberDetail } from "@/types";

const members = ref<MemberDetail[]>([]);
const total = ref(0);
const page = ref(1);
const limit = 15;
const search = ref("");
const statusFilter = ref("");
const loading = ref(false);

const confirmDialog = useConfirmDialog();
const toast = useToast();

async function load() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    params.set("page", String(page.value));
    params.set("limit", String(limit));
    if (search.value) params.set("q", search.value);
    if (statusFilter.value) params.set("status", statusFilter.value);
    const res = await listMembers(`?${params}`);
    members.value = res.data;
    total.value = (res.meta?.total as number) ?? 0;
  } finally {
    loading.value = false;
  }
}

function onSearch() {
  page.value = 1;
  load();
}

let searchTimer: ReturnType<typeof setTimeout>;
function onSearchInput() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => { page.value = 1; load(); }, 300);
}

watch(statusFilter, () => { page.value = 1; load(); });

async function remove(id: number) {
  const allowed = await confirmDialog.confirm({
    title: "Delete member?",
    message: "This will permanently delete the member and their subscription data.",
    confirmText: "Delete",
    destructive: true,
  });
  if (!allowed) return;
  try {
    await deleteMember(id);
    await load();
    toast.success("Member deleted");
  } catch (e) {
    toast.error("Delete failed", e instanceof Error ? e.message : "Unable to delete member.");
  }
}

function subStatusCls(member: MemberDetail) {
  if (!member.subscription) return "bg-slate-100 text-slate-400";
  if (member.subscription.isActive) return "bg-emerald-100 text-emerald-700";
  return "bg-rose-100 text-rose-600";
}
function subStatusLabel(member: MemberDetail) {
  if (!member.subscription) return "No Plan";
  if (member.subscription.isActive) return "Active";
  return "Expired";
}

onMounted(load);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-6">
      <div class="admin-hero">
        <div class="space-y-2">
          <p class="page-kicker">Member Services</p>
          <h1 class="page-title">Members</h1>
        </div>
        <div class="admin-actions">
          <span class="admin-pill bg-slate-100 text-slate-600">{{ total }} total</span>
          <router-link to="/admin/members/new" class="admin-primary-button">
            <Plus class="h-4 w-4" />
            Add Member
          </router-link>
        </div>
      </div>

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <!-- Table header + inline filters -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-2.5">
          <div class="flex items-center gap-2.5">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100">
              <Users class="h-3.5 w-3.5 text-slate-600" />
            </div>
            <h2 class="text-sm font-semibold text-slate-900">Member Registry</h2>
            <span class="admin-pill bg-slate-100 text-slate-500">{{ total }}</span>
          </div>
          <div class="flex items-center gap-2">
            <select
              v-model="statusFilter"
              class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
            >
              <option value="">All statuses</option>
              <option value="active">Active</option>
              <option value="suspended">Suspended</option>
            </select>
            <div class="relative">
              <Search class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
              <input
                v-model="search"
                type="text"
                placeholder="Search name or email…"
                class="w-full sm:w-64 rounded-lg border border-slate-300 py-1.5 pl-9 pr-3 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                @keyup.enter="onSearch"
                @input="onSearchInput"
              />
            </div>
          </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2.5 text-xs font-semibold text-slate-500">Name</th>
                <th class="px-4 py-2.5 text-xs font-semibold text-slate-500">Email</th>
                <th class="px-4 py-2.5 text-xs font-semibold text-slate-500">Institution</th>
                <th class="px-4 py-2.5 text-xs font-semibold text-slate-500">Status</th>
                <th class="px-4 py-2.5 text-xs font-semibold text-slate-500">Subscription</th>
                <th class="px-4 py-2.5 text-right text-xs font-semibold text-slate-500">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="loading">
                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-400">Loading…</td>
              </tr>
              <template v-else>
                <tr
                  v-for="member in members"
                  :key="member.id"
                  class="transition-colors hover:bg-slate-50"
                >
                  <td class="px-4 py-2.5 font-medium text-slate-900">
                    <router-link :to="'/admin/members/' + member.id" class="hover:text-blue-600">
                      {{ member.name }}
                    </router-link>
                  </td>
                  <td class="px-4 py-2.5 text-slate-500">{{ member.email }}</td>
                  <td class="px-4 py-2.5 text-slate-500">{{ member.institution ?? "—" }}</td>
                  <td class="px-4 py-2.5">
                    <span
                      class="admin-pill"
                      :class="member.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-600'"
                    >
                      <CheckCircle2 v-if="member.status === 'active'" class="h-3 w-3" />
                      <XCircle v-else class="h-3 w-3" />
                      {{ member.status === 'active' ? 'Active' : 'Suspended' }}
                    </span>
                  </td>
                  <td class="px-4 py-2.5">
                    <template v-if="member.subscription">
                      <!-- Plan name — primary, violet to distinguish from status -->
                      <span class="admin-pill bg-violet-100 text-violet-700 font-semibold">
                        <CreditCard class="h-3 w-3" />
                        {{ member.subscription.packageName }}
                      </span>
                      <!-- Status + expiry on second line -->
                      <div class="mt-1 flex items-center gap-1.5">
                        <span class="admin-pill text-[10px] leading-none py-0.5" :class="subStatusCls(member)">
                          <CheckCircle2 v-if="member.subscription.isActive" class="h-2.5 w-2.5" />
                          <XCircle v-else class="h-2.5 w-2.5" />
                          {{ subStatusLabel(member) }}
                        </span>
                        <span class="text-[10px] text-slate-400">
                          expires {{ new Date(member.subscription.expiresAt).toLocaleDateString('en-MY', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                        </span>
                      </div>
                    </template>
                    <span v-else class="admin-pill bg-slate-100 text-slate-400">
                      No Plan
                    </span>
                  </td>
                  <td class="px-4 py-2.5 text-right">
                    <div class="flex items-center justify-end gap-1.5">
                      <router-link
                        :to="'/admin/members/' + member.id"
                        class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                      >
                        <Pencil class="h-3.5 w-3.5" />
                        <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">Edit</span>
                      </router-link>
                      <button
                        class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600"
                        @click="remove(member.id)"
                      >
                        <Trash2 class="h-3.5 w-3.5" />
                        <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">Delete</span>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="members.length === 0">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">No members found.</td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="total > limit" class="flex items-center justify-between border-t border-slate-100 px-4 py-3">
          <span class="text-xs text-slate-500">Showing {{ (page - 1) * limit + 1 }}–{{ Math.min(page * limit, total) }} of {{ total }}</span>
          <div class="flex gap-2">
            <button
              :disabled="page === 1"
              class="rounded px-3 py-1 text-xs text-slate-600 border border-slate-200 hover:bg-slate-50 disabled:opacity-40"
              @click="page--; load()"
            >Prev</button>
            <button
              :disabled="page * limit >= total"
              class="rounded px-3 py-1 text-xs text-slate-600 border border-slate-200 hover:bg-slate-50 disabled:opacity-40"
              @click="page++; load()"
            >Next</button>
          </div>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
