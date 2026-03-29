<script setup lang="ts">
import { onMounted, ref, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { ArrowLeft, CreditCard, Trash2 } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import FormField from "@/components/FormField.vue";
import LoadingSkeleton from "@/components/LoadingSkeleton.vue";
import { useFormDirty } from "@/composables/useFormDirty";
import { useFieldValidation } from "@/composables/useFieldValidation";
import {
  getMember,
  createMember,
  updateMember,
  updateMemberStatus,
  getMemberSubscription,
  assignMemberSubscription,
  removeMemberSubscription,
  listPackages,
} from "@/api/cms";
import { useToast } from "@/composables/useToast";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import type { MemberDetail, MemberSubscription, Package } from "@/types";

const route = useRoute();
const router = useRouter();
const toast = useToast();
const confirmDialog = useConfirmDialog();

const isNew = computed(() => !route.params.id || route.params.id === "new");
const memberId = computed(() => (isNew.value ? null : Number(route.params.id)));

// Profile form
const form = ref({
  name: "",
  email: "",
  password: "",
  gender: "",
  phone: "",
  institution: "",
  workStudyStatus: "" as "" | "working" | "studying",
  country: "",
  status: "active" as "active" | "suspended",
});

const { isDirty, resetDirty } = useFormDirty(form);
const { errors, validateAll, clearError } = useFieldValidation({
  name: { required: true, message: "Full name is required" },
  email: { required: true, message: "Email is required" },
});

// Subscription form
const packages = ref<Package[]>([]);
const subscription = ref<MemberSubscription | null>(null);
const subForm = ref({
  packageId: 0,
  subscribedAt: "",
  expiresAt: "",
  notes: "",
});
const showSubForm = ref(false);
const saving = ref(false);
const savingSub = ref(false);

async function load() {
  const [pkgsRes] = await Promise.all([listPackages("?limit=100")]);
  packages.value = pkgsRes.data.filter((p) => p.isActive);

  if (!isNew.value && memberId.value) {
    const [memberRes, subRes] = await Promise.all([
      getMember(memberId.value),
      getMemberSubscription(memberId.value),
    ]);
    const m: MemberDetail = memberRes.data;
    form.value = {
      name: m.name,
      email: m.email,
      password: "",
      gender: m.gender ?? "",
      phone: m.phone ?? "",
      institution: m.institution ?? "",
      workStudyStatus: m.workStudyStatus ?? "",
      country: m.country ?? "",
      status: m.status,
    };
    subscription.value = subRes.data ?? null;
    if (subscription.value) {
      subForm.value = {
        packageId: subscription.value.packageId ?? 0,
        subscribedAt: subscription.value.subscribedAt?.slice(0, 10) ?? "",
        expiresAt: subscription.value.expiresAt?.slice(0, 10) ?? "",
        notes: subscription.value.notes ?? "",
      };
    }
  }
}

async function save() {
  if (!validateAll({ name: form.value.name, email: form.value.email })) return;
  saving.value = true;
  try {
    const payload: Record<string, unknown> = {
      name: form.value.name,
      email: form.value.email,
      gender: form.value.gender || undefined,
      phone: form.value.phone || undefined,
      institution: form.value.institution || undefined,
      workStudyStatus: form.value.workStudyStatus || undefined,
      country: form.value.country || undefined,
      status: form.value.status,
    };
    if (form.value.password) payload.password = form.value.password;

    if (isNew.value) {
      if (!form.value.password) {
        toast.error("Password required", "Please set a password for the new member.");
        return;
      }
      payload.password = form.value.password;
      await createMember(payload as never);
      toast.success("Member created");
      router.push("/admin/members");
    } else {
      await updateMember(memberId.value!, payload as never);
      toast.success("Member updated");
      resetDirty();
    }
  } catch (e) {
    toast.error("Save failed", e instanceof Error ? e.message : "Could not save member.");
  } finally {
    saving.value = false;
  }
}

async function changeStatus(newStatus: "active" | "suspended") {
  if (!memberId.value) return;
  try {
    await updateMemberStatus(memberId.value, newStatus);
    form.value.status = newStatus;
    toast.success(`Member ${newStatus}`);
  } catch {
    toast.error("Failed to update status");
  }
}

async function saveSub() {
  if (!memberId.value || !subForm.value.packageId) return;
  savingSub.value = true;
  try {
    const pkg = packages.value.find((p) => p.id === subForm.value.packageId);
    const payload: Record<string, unknown> = { packageId: subForm.value.packageId };
    if (subForm.value.subscribedAt) payload.subscribedAt = subForm.value.subscribedAt;
    if (subForm.value.expiresAt) payload.expiresAt = subForm.value.expiresAt;
    if (subForm.value.notes) payload.notes = subForm.value.notes;

    const res = await assignMemberSubscription(memberId.value, payload as never);
    subscription.value = res.data;
    showSubForm.value = false;
    toast.success(`Subscription assigned: ${pkg?.name}`);
  } catch (e) {
    toast.error("Failed to assign subscription", e instanceof Error ? e.message : "");
  } finally {
    savingSub.value = false;
  }
}

async function removeSub() {
  if (!memberId.value) return;
  const ok = await confirmDialog.confirm({
    title: "Remove subscription?",
    message: "The member will lose access immediately.",
    confirmText: "Remove",
    destructive: true,
  });
  if (!ok) return;
  try {
    await removeMemberSubscription(memberId.value);
    subscription.value = null;
    showSubForm.value = false;
    toast.success("Subscription removed");
  } catch {
    toast.error("Failed to remove subscription");
  }
}

function openSubForm() {
  if (subscription.value) {
    subForm.value = {
      packageId: subscription.value.packageId ?? 0,
      subscribedAt: subscription.value.subscribedAt?.slice(0, 10) ?? "",
      expiresAt: subscription.value.expiresAt?.slice(0, 10) ?? "",
      notes: subscription.value.notes ?? "",
    };
  } else {
    subForm.value = { packageId: packages.value[0]?.id ?? 0, subscribedAt: "", expiresAt: "", notes: "" };
  }
  showSubForm.value = true;
}

function onPackageChange() {
  const pkg = packages.value.find((p) => p.id === subForm.value.packageId);
  if (pkg && subForm.value.subscribedAt) {
    const start = new Date(subForm.value.subscribedAt);
    start.setMonth(start.getMonth() + pkg.durationMonths);
    subForm.value.expiresAt = start.toISOString().slice(0, 10);
  }
}

function onStartDateChange() {
  const pkg = packages.value.find((p) => p.id === subForm.value.packageId);
  if (pkg && subForm.value.subscribedAt) {
    const start = new Date(subForm.value.subscribedAt);
    start.setMonth(start.getMonth() + pkg.durationMonths);
    subForm.value.expiresAt = start.toISOString().slice(0, 10);
  }
}

onMounted(load);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-3xl space-y-6">
      <div class="admin-hero">
        <div class="space-y-2">
          <p class="page-kicker">Member Services</p>
          <h1 class="page-title">{{ isNew ? "Add Member" : "Edit Member" }}</h1>
          <p class="page-subtitle">Create subscriber accounts, update profile details, and manage subscriptions or access status with clearer sections.</p>
        </div>
        <div class="admin-actions">
          <router-link to="/admin/members" class="admin-secondary-button">
            <ArrowLeft class="h-4 w-4" />
            Members
          </router-link>
        </div>
      </div>

      <!-- Status controls (edit only) -->
      <div v-if="!isNew" class="flex flex-wrap gap-2">
        <button
          v-if="form.status === 'active'"
          class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-sm font-medium text-rose-600 hover:bg-rose-100"
          @click="changeStatus('suspended')"
        >
          Suspend Member
        </button>
        <button
          v-else
          class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-600 hover:bg-emerald-100"
          @click="changeStatus('active')"
        >
          Activate Member
        </button>
      </div>

      <!-- Profile form -->
      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Profile Information</h2>
        </div>
        <div class="grid gap-4 p-4 sm:grid-cols-2">
          <div class="sm:col-span-2 grid grid-cols-2 gap-4">
            <FormField label="Full Name" :required="true" :error="errors.name">
              <input v-model="form.name" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" @input="clearError('name')" />
            </FormField>
            <FormField label="Email" :required="true" :error="errors.email">
              <input v-model="form.email" type="email" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" @input="clearError('email')" />
            </FormField>
          </div>
          <FormField :label="isNew ? 'Password' : 'Password'" :required="isNew" :help-text="isNew ? undefined : 'Leave blank to keep the current password'">
            <input v-model="form.password" type="password" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" />
          </FormField>
          <FormField label="Gender">
            <select v-model="form.gender" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
              <option value="">-- Select --</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
          </FormField>
          <FormField label="Phone">
            <input v-model="form.phone" type="tel" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" />
          </FormField>
          <FormField label="Country">
            <input v-model="form.country" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" />
          </FormField>
          <FormField label="Institution">
            <input v-model="form.institution" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" />
          </FormField>
          <FormField label="Work / Study Status">
            <select v-model="form.workStudyStatus" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
              <option value="">-- Select --</option>
              <option value="working">Working</option>
              <option value="studying">Studying</option>
            </select>
          </FormField>
        </div>
        <div class="flex justify-end border-t border-slate-100 px-4 py-3">
          <button
            :disabled="saving"
            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-50"
            @click="save"
          >
            {{ saving ? "Saving…" : isNew ? "Create Member" : "Save Changes" }}
          </button>
        </div>
      </article>

      <!-- Subscription panel (edit only) -->
      <article v-if="!isNew" class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
          <div class="flex items-center gap-2">
            <CreditCard class="h-4 w-4 text-blue-600" />
            <h2 class="text-sm font-semibold text-slate-900">Subscription</h2>
          </div>
          <div class="flex gap-2">
            <button
              v-if="subscription"
              class="flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-100"
              @click="removeSub"
            >
              <Trash2 class="h-3.5 w-3.5" /> Remove
            </button>
            <button
              class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700"
              @click="openSubForm"
            >
              {{ subscription ? "Change Package" : "Assign Package" }}
            </button>
          </div>
        </div>

        <!-- Current subscription info -->
        <div v-if="subscription && !showSubForm" class="p-4 space-y-2">
          <div class="flex flex-wrap gap-4 text-sm">
            <div>
              <span class="text-xs text-slate-500 block">Package</span>
              <span class="font-medium text-slate-900">{{ subscription.packageName }}</span>
            </div>
            <div>
              <span class="text-xs text-slate-500 block">Price</span>
              <span class="font-medium text-slate-900">RM {{ subscription.packagePrice }}</span>
            </div>
            <div>
              <span class="text-xs text-slate-500 block">Started</span>
              <span class="font-medium text-slate-900">{{ new Date(subscription.subscribedAt).toLocaleDateString() }}</span>
            </div>
            <div>
              <span class="text-xs text-slate-500 block">Expires</span>
              <span class="font-medium" :class="subscription.isActive ? 'text-emerald-700' : 'text-rose-600'">
                {{ new Date(subscription.expiresAt).toLocaleDateString() }}
              </span>
            </div>
            <div>
              <span class="text-xs text-slate-500 block">Status</span>
              <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium" :class="subscription.isActive ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-600'">
                {{ subscription.isActive ? "Active" : "Expired" }}
              </span>
            </div>
          </div>
          <div v-if="subscription.notes" class="mt-2 text-xs text-slate-500">
            Note: {{ subscription.notes }}
          </div>
        </div>

        <div v-else-if="!subscription && !showSubForm" class="px-4 py-6 text-center text-sm text-slate-400">
          No active subscription. Click "Assign Package" to add one.
        </div>

        <!-- Subscription assign/edit form -->
        <div v-if="showSubForm" class="p-4 space-y-4">
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-700">Package <span class="text-rose-500">*</span></label>
            <select
              v-model="subForm.packageId"
              class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
              @change="onPackageChange"
            >
              <option :value="0" disabled>— Select package —</option>
              <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                {{ pkg.name }} — RM {{ pkg.price }} / {{ pkg.durationMonths }} month{{ pkg.durationMonths > 1 ? 's' : '' }}
              </option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-700">Start Date</label>
              <input
                v-model="subForm.subscribedAt"
                type="date"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                @change="onStartDateChange"
              />
              <span class="text-xs text-slate-400">Leave blank to use today</span>
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-700">Expiry Date <span class="text-slate-400">(auto-calculated)</span></label>
              <input
                v-model="subForm.expiresAt"
                type="date"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
              />
              <span class="text-xs text-slate-400">Override the auto-calculated date if needed</span>
            </div>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-700">Internal Notes</label>
            <input v-model="subForm.notes" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" placeholder="Optional admin note" />
          </div>
          <div class="flex gap-2 justify-end">
            <button class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50" @click="showSubForm = false">Cancel</button>
            <button
              :disabled="savingSub || !subForm.packageId"
              class="rounded-lg bg-blue-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
              @click="saveSub"
            >
              {{ savingSub ? "Saving…" : "Save Subscription" }}
            </button>
          </div>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
