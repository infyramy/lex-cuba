<script setup lang="ts">
import { onMounted, ref } from "vue";
import {
  Users, Plus, Pencil, Trash2,
  CheckCircle2, XCircle, X, Save, KeyRound, Eye, EyeOff,
} from "lucide-vue-next";
import AdminLayout from "@/layouts/AdminLayout.vue";
import { listUsers, getUser, createUser, updateUser, deleteUser, listRoles } from "@/api/cms";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import { useToast } from "@/composables/useToast";
import type { Role, UserDetail } from "@/types";

const users       = ref<UserDetail[]>([]);
const roles       = ref<Role[]>([]);
const confirmDialog = useConfirmDialog();
const toast       = useToast();

// ── Modal state ────────────────────────────────────────────────────────────
const modalOpen   = ref(false);
const modalMode   = ref<"create" | "edit">("create");
const modalSaving = ref(false);
const editId      = ref<number | null>(null);
const showPass    = ref(false);

const form = ref({
  name: "",
  email: "",
  role: "admin",
  isActive: true,
  password: "",
  confirmPassword: "",
});
const formError = ref("");

async function load() {
  const [uRes, rRes] = await Promise.all([listUsers(), listRoles()]);
  users.value = uRes.data;
  roles.value = rRes.data;
}

function openCreate() {
  modalMode.value = "create";
  editId.value    = null;
  form.value      = { name: "", email: "", role: roles.value[0]?.name ?? "admin", isActive: true, password: "", confirmPassword: "" };
  formError.value = "";
  showPass.value  = false;
  modalOpen.value = true;
}

async function openEdit(id: number) {
  modalMode.value = "edit";
  editId.value    = id;
  formError.value = "";
  showPass.value  = false;
  form.value      = { name: "", email: "", role: "admin", isActive: true, password: "", confirmPassword: "" };
  modalOpen.value = true;
  try {
    const res = await getUser(id);
    const u   = res.data;
    form.value = { name: u.name, email: u.email, role: u.role, isActive: u.isActive, password: "", confirmPassword: "" };
  } catch (e) {
    toast.error("Load failed", e instanceof Error ? e.message : "Unable to load user.");
    modalOpen.value = false;
  }
}

function closeModal() { modalOpen.value = false; }

async function save() {
  formError.value = "";
  if (!form.value.name || !form.value.email) {
    formError.value = "Name and email are required.";
    return;
  }
  if (modalMode.value === "create" && !form.value.password) {
    formError.value = "Password is required for new users.";
    return;
  }
  if (form.value.password && form.value.password !== form.value.confirmPassword) {
    formError.value = "Passwords do not match.";
    return;
  }
  modalSaving.value = true;
  try {
    if (modalMode.value === "create") {
      await createUser({ name: form.value.name, email: form.value.email, password: form.value.password, role: form.value.role, isActive: form.value.isActive });
      toast.success("User created");
    } else {
      const payload: Record<string, unknown> = { name: form.value.name, email: form.value.email, role: form.value.role, isActive: form.value.isActive };
      if (form.value.password) payload.password = form.value.password;
      await updateUser(editId.value!, payload);
      toast.success("User updated");
    }
    closeModal();
    await load();
  } catch (e) {
    formError.value = e instanceof Error ? e.message : "Save failed.";
  } finally {
    modalSaving.value = false;
  }
}

async function remove(id: number) {
  const allowed = await confirmDialog.confirm({
    title: "Delete user?",
    message: "This action cannot be undone.",
    confirmText: "Delete",
    destructive: true,
  });
  if (!allowed) return;
  try {
    await deleteUser(id);
    await load();
    toast.success("User deleted");
  } catch (e) {
    toast.error("Delete failed", e instanceof Error ? e.message : "Unable to delete user.");
  }
}

onMounted(load);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-6">

      <!-- Hero -->
      <div class="admin-hero">
        <div class="space-y-1">
          <p class="page-kicker">System Administration</p>
          <h1 class="page-title">Admin Users</h1>
        </div>
        <button class="admin-primary-button" @click="openCreate">
          <Plus class="h-4 w-4" />
          Add User
        </button>
      </div>

      <!-- Table card -->
      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
          <div class="flex items-center gap-2.5">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100">
              <Users class="h-3.5 w-3.5 text-slate-600" />
            </div>
            <h2 class="text-sm font-semibold text-slate-900">Administrative Accounts</h2>
            <span class="admin-pill bg-slate-100 text-slate-500">{{ users.length }}</span>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2.5 text-xs font-semibold text-slate-500">Name</th>
                <th class="px-4 py-2.5 text-xs font-semibold text-slate-500">Email</th>
                <th class="px-4 py-2.5 text-xs font-semibold text-slate-500">Role</th>
                <th class="px-4 py-2.5 text-xs font-semibold text-slate-500">Status</th>
                <th class="px-4 py-2.5 text-right text-xs font-semibold text-slate-500">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="user in users" :key="user.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2.5 font-medium text-slate-900">{{ user.name }}</td>
                <td class="px-4 py-2.5 text-slate-500">{{ user.email }}</td>
                <td class="px-4 py-2.5">
                  <span class="admin-pill bg-slate-100 text-slate-700">{{ user.role }}</span>
                </td>
                <td class="px-4 py-2.5">
                  <span v-if="user.isActive" class="admin-pill bg-emerald-100 text-emerald-700">
                    <CheckCircle2 class="h-3 w-3" /> Active
                  </span>
                  <span v-else class="admin-pill bg-slate-100 text-slate-500">
                    <XCircle class="h-3 w-3" /> Inactive
                  </span>
                </td>
                <td class="px-4 py-2.5 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button
                      class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                      @click="openEdit(user.id)"
                    >
                      <Pencil class="h-3.5 w-3.5" />
                      <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">Edit</span>
                    </button>
                    <button
                      class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600"
                      @click="remove(user.id)"
                    >
                      <Trash2 class="h-3.5 w-3.5" />
                      <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">Delete</span>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="users.length === 0">
                <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-400">No admin users found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </div>

    <!-- ══ Add / Edit Modal ══ -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-150 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-100 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4" @click.self="closeModal">
          <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white shadow-2xl">

            <!-- Modal header -->
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
              <div class="flex items-center gap-2">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100">
                  <Users class="h-3.5 w-3.5 text-slate-600" />
                </div>
                <h3 class="text-sm font-semibold text-slate-900">
                  {{ modalMode === 'create' ? 'Add Admin User' : 'Edit Admin User' }}
                </h3>
              </div>
              <button class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors" @click="closeModal">
                <X class="h-4 w-4" />
              </button>
            </div>

            <!-- Modal body -->
            <div class="space-y-3.5 p-5">
              <div v-if="formError" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                {{ formError }}
              </div>

              <!-- Name -->
              <div class="space-y-1.5">
                <label class="text-xs font-semibold text-slate-600">Full Name</label>
                <input v-model="form.name" type="text" placeholder="e.g. Ahmad Razali" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" />
              </div>

              <!-- Email -->
              <div class="space-y-1.5">
                <label class="text-xs font-semibold text-slate-600">Email Address</label>
                <input v-model="form.email" type="email" placeholder="user@example.com" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" />
              </div>

              <!-- Role + Status (row) -->
              <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                  <label class="text-xs font-semibold text-slate-600">Role</label>
                  <select v-model="form.role" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    <option v-for="r in roles" :key="r.id" :value="r.name">{{ r.name }}</option>
                  </select>
                </div>
                <div class="space-y-1.5">
                  <label class="text-xs font-semibold text-slate-600">Status</label>
                  <select v-model="form.isActive" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    <option :value="true">Active</option>
                    <option :value="false">Inactive</option>
                  </select>
                </div>
              </div>

              <!-- Password section -->
              <div class="rounded-lg border border-slate-100 bg-slate-50 p-3 space-y-2.5">
                <div class="flex items-center gap-1.5">
                  <KeyRound class="h-3.5 w-3.5 text-slate-400" />
                  <p class="text-xs font-semibold text-slate-600">
                    {{ modalMode === 'create' ? 'Password (required)' : 'Reset Password (optional)' }}
                  </p>
                </div>
                <div class="space-y-1.5">
                  <div class="relative">
                    <input
                      v-model="form.password"
                      :type="showPass ? 'text' : 'password'"
                      placeholder="••••••••"
                      class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pr-9 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                    />
                    <button type="button" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" @click="showPass = !showPass">
                      <Eye v-if="!showPass" class="h-3.5 w-3.5" />
                      <EyeOff v-else class="h-3.5 w-3.5" />
                    </button>
                  </div>
                  <input
                    v-if="form.password"
                    v-model="form.confirmPassword"
                    type="password"
                    placeholder="Confirm password"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                  />
                </div>
              </div>
            </div>

            <!-- Modal footer -->
            <div class="flex items-center justify-end gap-3 border-t border-slate-100 px-5 py-3.5">
              <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50" @click="closeModal">
                Cancel
              </button>
              <button
                class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-slate-800 disabled:opacity-50"
                :disabled="modalSaving"
                @click="save"
              >
                <Save class="h-3.5 w-3.5" />
                {{ modalSaving ? 'Saving…' : (modalMode === 'create' ? 'Create User' : 'Save Changes') }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </AdminLayout>
</template>
