<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { Shield, Plus, Pencil, Trash2, Save, X, Check, ChevronDown } from "lucide-vue-next";
import AdminLayout from "@/layouts/AdminLayout.vue";
import FormField from "@/components/FormField.vue";
import LoadingSkeleton from "@/components/LoadingSkeleton.vue";
import EmptyState from "@/components/EmptyState.vue";
import { listRoles, createRole, updateRole, deleteRole, listPermissions } from "@/api/cms";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import { useToast } from "@/composables/useToast";
import type { Role, RoleInput } from "@/types";

const roles      = ref<Role[]>([]);
const allPerms   = ref<string[]>([]);
const loading    = ref(true);
const showForm   = ref(false);
const editingId  = ref<number | null>(null);
const saving     = ref(false);
const confirmDialog = useConfirmDialog();
const toast      = useToast();

const form = ref<RoleInput>({ name: "", description: "", permissions: [] });

// ── Human-readable labels ─────────────────────────────────────────────────
const groupLabels: Record<string, string> = {
  notes:          "Notes",
  case_summaries: "Case Summaries",
  questions:      "Question Bank",
  categories:     "Topics",
  topic_links:    "Topic Links",
  free_links:     "Free Links",
  media:          "Media Library",
  users:          "Admin Users",
  members:        "Members",
  packages:       "Packages",
  roles:          "Roles & Permissions",
  settings:       "Settings",
  audit:          "Audit Logs",
};

const actionLabels: Record<string, string> = {
  view:   "View",
  create: "Add",
  edit:   "Edit",
  delete: "Delete",
  upload: "Upload",
  read:   "Read",
};

function permLabel(perm: string) {
  const [, action] = perm.split(".");
  return actionLabels[action] ?? action;
}

function groupLabel(group: string) {
  return groupLabels[group] ?? group.replace(/_/g, " ").replace(/\b\w/g, c => c.toUpperCase());
}

// Build grouped permissions from the fetched list
const groupedPermissions = computed(() => {
  return allPerms.value.reduce<Record<string, string[]>>((acc, p) => {
    const group = p.split(".")[0];
    if (!acc[group]) acc[group] = [];
    acc[group].push(p);
    return acc;
  }, {});
});

// Toggle all in a group
function allInGroupSelected(group: string) {
  return groupedPermissions.value[group]?.every(p => form.value.permissions.includes(p)) ?? false;
}
function toggleGroup(group: string) {
  const perms = groupedPermissions.value[group] ?? [];
  if (allInGroupSelected(group)) {
    form.value.permissions = form.value.permissions.filter(p => !perms.includes(p));
  } else {
    const toAdd = perms.filter(p => !form.value.permissions.includes(p));
    form.value.permissions.push(...toAdd);
  }
}

function togglePermission(perm: string) {
  const idx = form.value.permissions.indexOf(perm);
  if (idx >= 0) form.value.permissions.splice(idx, 1);
  else form.value.permissions.push(perm);
}

// Friendly display of selected permissions for a role
function permDisplayList(perms: string[]) {
  // Group them: "Notes: View, Add, Edit"
  const groups: Record<string, string[]> = {};
  for (const p of perms) {
    const [g, a] = p.split(".");
    if (!groups[g]) groups[g] = [];
    groups[g].push(actionLabels[a] ?? a);
  }
  return Object.entries(groups).map(([g, actions]) => `${groupLabel(g)}: ${actions.join(", ")}`);
}

async function load() {
  loading.value = true;
  try {
    const [rRes, pRes] = await Promise.all([listRoles(), listPermissions()]);
    roles.value    = rRes.data;
    allPerms.value = pRes.data;
  } finally {
    loading.value = false;
  }
}

function startNew() {
  editingId.value = null;
  form.value      = { name: "", description: "", permissions: [] };
  showForm.value  = true;
}

function startEdit(role: Role) {
  editingId.value = role.id;
  form.value      = { name: role.name, description: role.description, permissions: [...role.permissions] };
  showForm.value  = true;
}

function cancelForm() { showForm.value = false; editingId.value = null; }

async function save() {
  saving.value = true;
  const wasEdit = editingId.value !== null;
  try {
    if (wasEdit && editingId.value !== null) await updateRole(editingId.value, form.value);
    else await createRole(form.value);
    await load();
    showForm.value  = false;
    editingId.value = null;
    toast.success(wasEdit ? "Role updated" : "Role created");
  } catch (e) {
    toast.error("Save failed", e instanceof Error ? e.message : "Unable to save role.");
  } finally {
    saving.value = false;
  }
}

async function remove(id: number) {
  const allowed = await confirmDialog.confirm({
    title: "Delete role?",
    message: "This cannot be undone. You cannot delete a role that is in use.",
    confirmText: "Delete",
    destructive: true,
  });
  if (!allowed) return;
  try {
    await deleteRole(id);
    await load();
    toast.success("Role deleted");
  } catch (e) {
    toast.error("Delete failed", e instanceof Error ? e.message : "Unable to delete role. It may be in use.");
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
          <p class="page-kicker">Access Control</p>
          <h1 class="page-title">Roles & Permissions</h1>
        </div>
        <button class="admin-primary-button" @click="startNew">
          <Plus class="h-4 w-4" />
          New Role
        </button>
      </div>

      <!-- ── Form (inline, above the list) ── -->
      <article v-if="showForm" class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
          <div class="flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg" :class="editingId ? 'bg-amber-100' : 'bg-violet-100'">
              <Shield class="h-3.5 w-3.5" :class="editingId ? 'text-amber-600' : 'text-violet-600'" />
            </div>
            <h2 class="text-sm font-semibold text-slate-900">{{ editingId ? 'Edit Role' : 'New Role' }}</h2>
          </div>
          <button class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100" @click="cancelForm">
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="p-5 space-y-4">
          <!-- Name + Description -->
          <div class="grid gap-3 sm:grid-cols-2">
            <FormField label="Role Name" :required="true">
              <input v-model="form.name" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" placeholder="e.g. Content Editor" />
            </FormField>
            <FormField label="Description" help-text="A brief explanation visible only to other admins">
              <input v-model="form.description" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" placeholder="Brief description of this role" />
            </FormField>
          </div>

          <!-- Permissions -->
          <div class="space-y-1.5">
            <div class="flex items-center justify-between">
              <label class="text-xs font-semibold text-slate-600">What can this role do?</label>
              <span class="text-xs text-slate-400">{{ form.permissions.length }} permission{{ form.permissions.length !== 1 ? 's' : '' }} selected</span>
            </div>
            <p class="text-xs text-slate-400">Permissions control what each role can view, create, edit, or delete</p>

            <div class="divide-y divide-slate-100 rounded-xl border border-slate-200 overflow-hidden">
              <div v-for="(perms, group) in groupedPermissions" :key="group" class="bg-white">
                <!-- Group header with select-all toggle -->
                <div class="flex items-center gap-3 px-4 py-2.5 bg-slate-50/70">
                  <button
                    class="flex h-4.5 w-4.5 items-center justify-center rounded border transition-colors"
                    :class="allInGroupSelected(group as string) ? 'border-violet-500 bg-violet-500 text-white' : 'border-slate-300 bg-white hover:border-slate-400'"
                    @click="toggleGroup(group as string)"
                  >
                    <Check v-if="allInGroupSelected(group as string)" class="h-2.5 w-2.5" />
                  </button>
                  <span class="text-xs font-semibold text-slate-700">{{ groupLabel(group as string) }}</span>
                </div>
                <!-- Actions for this group -->
                <div class="flex flex-wrap gap-2 px-4 py-2.5">
                  <button
                    v-for="perm in perms"
                    :key="perm"
                    class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1 text-xs font-medium transition-all"
                    :class="form.permissions.includes(perm)
                      ? 'border-violet-300 bg-violet-50 text-violet-700'
                      : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300 hover:bg-slate-50'"
                    @click="togglePermission(perm)"
                  >
                    <span
                      class="inline-flex h-3.5 w-3.5 items-center justify-center rounded-full"
                      :class="form.permissions.includes(perm) ? 'bg-violet-500 text-white' : 'bg-slate-200 text-slate-400'"
                    >
                      <Check v-if="form.permissions.includes(perm)" class="h-2 w-2" />
                    </span>
                    {{ permLabel(perm) }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer actions -->
          <div class="flex items-center gap-3 pt-1">
            <button
              class="flex items-center gap-2 rounded-lg bg-slate-900 px-5 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 disabled:opacity-50"
              :disabled="saving || !form.name"
              @click="save"
            >
              <Save class="h-4 w-4" />
              {{ saving ? 'Saving…' : (editingId ? 'Update Role' : 'Create Role') }}
            </button>
            <button class="rounded-lg border border-slate-200 px-5 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50" @click="cancelForm">
              Cancel
            </button>
          </div>
        </div>
      </article>

      <!-- ── Roles list ── -->
      <article class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-2.5 border-b border-slate-100 px-5 py-3.5">
          <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50">
            <Shield class="h-3.5 w-3.5 text-amber-600" />
          </div>
          <h2 class="text-sm font-semibold text-slate-900">All Roles</h2>
          <span class="admin-pill bg-slate-100 text-slate-500">{{ roles.length }}</span>
        </div>
        <div class="divide-y divide-slate-100">
          <div v-if="loading" class="p-5">
            <LoadingSkeleton variant="card" :lines="3" />
          </div>
          <div v-for="role in roles" v-else :key="role.id" class="px-5 py-4 transition-colors hover:bg-slate-50/60">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                  <p class="text-sm font-semibold text-slate-900">{{ role.name }}</p>
                  <span class="admin-pill bg-slate-100 text-slate-500">{{ role.permissions.length }} permissions</span>
                </div>
                <p v-if="role.description" class="mt-0.5 text-xs text-slate-400">{{ role.description }}</p>

                <!-- Grouped permission summary -->
                <div v-if="role.permissions.length > 0" class="mt-2.5 flex flex-wrap gap-1.5">
                  <span
                    v-for="line in permDisplayList(role.permissions)"
                    :key="line"
                    class="rounded-md bg-slate-50 border border-slate-200 px-2 py-0.5 text-xs text-slate-600"
                  >{{ line }}</span>
                </div>
                <p v-else class="mt-1 text-xs text-slate-400 italic">No permissions assigned</p>
              </div>
              <div class="flex shrink-0 items-center gap-1.5">
                <button
                  class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                  title="Edit"
                  @click="startEdit(role)"
                >
                  <Pencil class="h-3.5 w-3.5" />
                </button>
                <button
                  class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600"
                  title="Delete"
                  @click="remove(role.id)"
                >
                  <Trash2 class="h-3.5 w-3.5" />
                </button>
              </div>
            </div>
          </div>
          <div v-if="!loading && roles.length === 0">
            <EmptyState :icon="Shield" title="No roles configured yet" description="Create roles to control what each admin user can access." action-label="New Role" @action="startNew" />
          </div>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
