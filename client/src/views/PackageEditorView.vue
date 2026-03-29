<script setup lang="ts">
import { onMounted, ref, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { ArrowLeft, Check } from "lucide-vue-next";
import AdminLayout from "@/layouts/AdminLayout.vue";
import FormField from "@/components/FormField.vue";
import LoadingSkeleton from "@/components/LoadingSkeleton.vue";
import { getPackage, createPackage, updatePackage, listCategories } from "@/api/cms";
import { useToast } from "@/composables/useToast";
import { useFormDirty } from "@/composables/useFormDirty";
import { useFieldValidation } from "@/composables/useFieldValidation";
import type { Category } from "@/types";

const route = useRoute();
const router = useRouter();
const toast = useToast();

const isNew = computed(() => !route.params.id || route.params.id === "new");
const packageId = computed(() => (isNew.value ? null : Number(route.params.id)));

const loading = ref(false);
const saving = ref(false);
const categories = ref<Category[]>([]);

const form = ref({
  name: "",
  description: "",
  price: 0,
  durationMonths: 1,
  chatbotAccess: false,
  accessibleCategoryIds: [] as number[],
  isActive: true,
});

const { isDirty, resetDirty } = useFormDirty(form);
const { errors, validateAll, clearError } = useFieldValidation({
  name: { required: true, message: "Package name is required" },
  price: { required: true, message: "Price is required" },
  durationMonths: { required: true, message: "Duration is required" },
});

async function loadCategories() {
  try {
    const res = await listCategories("?limit=200");
    categories.value = res.data;
  } catch {
    // Silently fail — categories list is secondary
  }
}

async function load() {
  loading.value = true;
  try {
    await loadCategories();
    if (!isNew.value && packageId.value) {
      const res = await getPackage(packageId.value);
      const p = res.data;
      form.value = {
        name: p.name,
        description: p.description ?? "",
        price: parseFloat(p.price),
        durationMonths: p.durationMonths,
        chatbotAccess: p.chatbotAccess,
        accessibleCategoryIds: p.accessibleCategoryIds ?? [],
        isActive: p.isActive,
      };
    }
  } catch (e) {
    toast.error("Load failed", e instanceof Error ? e.message : "Could not load package.");
  } finally {
    loading.value = false;
  }
}

function toggleCategory(id: number) {
  const idx = form.value.accessibleCategoryIds.indexOf(id);
  if (idx === -1) {
    form.value.accessibleCategoryIds.push(id);
  } else {
    form.value.accessibleCategoryIds.splice(idx, 1);
  }
}

function toggleAll() {
  if (form.value.accessibleCategoryIds.length === categories.value.length) {
    form.value.accessibleCategoryIds = [];
  } else {
    form.value.accessibleCategoryIds = categories.value.map((c) => c.id);
  }
}

const allSelected = computed(() => categories.value.length > 0 && form.value.accessibleCategoryIds.length === categories.value.length);

async function save() {
  if (!validateAll(form.value)) return;

  saving.value = true;
  try {
    const payload = { ...form.value };
    if (isNew.value) {
      await createPackage(payload);
      toast.success("Package created");
      resetDirty();
      router.push("/admin/packages");
    } else {
      await updatePackage(packageId.value!, payload);
      toast.success("Package updated");
      resetDirty();
    }
  } catch (e) {
    toast.error("Save failed", e instanceof Error ? e.message : "Could not save package.");
  } finally {
    saving.value = false;
  }
}

onMounted(load);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-xl space-y-6">
      <div class="admin-hero">
        <div class="space-y-2">
          <p class="page-kicker">Subscription Services</p>
          <h1 class="page-title">{{ isNew ? "New Package" : "Edit Package" }}</h1>
          <p class="page-subtitle">Configure pricing, select accessible topics, and manage package availability.</p>
        </div>
        <div class="admin-actions">
          <router-link to="/admin/packages" class="admin-secondary-button">
            <ArrowLeft class="h-4 w-4" />
            Packages
          </router-link>
        </div>
      </div>

      <div v-if="loading" class="py-12">
        <LoadingSkeleton variant="form" />
      </div>

      <template v-else>
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-900">Package Details</h2>
          </div>
          <div class="space-y-4 p-4">
            <FormField label="Package Name" required :error="errors.name">
              <input v-model="form.name" type="text" placeholder="e.g. Basic, Premium, Annual" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" @input="() => clearError('name')" />
            </FormField>
            <FormField label="Description">
              <textarea v-model="form.description" rows="2" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="Optional description for this package" />
            </FormField>
            <div class="grid grid-cols-2 gap-4">
              <FormField label="Price (RM)" required :error="errors.price" helpText="Price in Malaysian Ringgit (RM)">
                <input v-model.number="form.price" type="number" min="0" step="0.01" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" @input="() => clearError('price')" />
              </FormField>
              <FormField label="Duration (months)" required :error="errors.durationMonths" helpText="Number of months the subscription lasts">
                <input v-model.number="form.durationMonths" type="number" min="1" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" @input="() => clearError('durationMonths')" />
              </FormField>
            </div>
          </div>

          <!-- Topic Access Selection -->
          <div class="border-t border-slate-100 px-4 py-3">
            <div class="mb-3 flex items-center justify-between">
              <div>
                <h3 class="text-xs font-semibold text-slate-500">Topic Access</h3>
                <p class="text-xs text-slate-400">Select which topics this package grants access to</p>
              </div>
              <button
                type="button"
                class="rounded-lg px-2.5 py-1 text-xs font-medium transition-colors"
                :class="allSelected ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                @click="toggleAll"
              >
                {{ allSelected ? "Deselect All" : "Select All" }}
              </button>
            </div>
            <div v-if="categories.length === 0" class="py-4 text-center text-xs text-slate-400">
              No topics found. Create topics first.
            </div>
            <div v-else class="space-y-2">
              <label
                v-for="cat in categories"
                :key="cat.id"
                class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition-colors"
                :class="form.accessibleCategoryIds.includes(cat.id) ? 'border-blue-200 bg-blue-50' : 'border-slate-200 bg-white hover:bg-slate-50'"
              >
                <div
                  class="flex h-5 w-5 shrink-0 items-center justify-center rounded border transition-colors"
                  :class="form.accessibleCategoryIds.includes(cat.id) ? 'border-blue-500 bg-blue-500' : 'border-slate-300 bg-white'"
                >
                  <Check v-if="form.accessibleCategoryIds.includes(cat.id)" class="h-3 w-3 text-white" />
                </div>
                <input
                  type="checkbox"
                  :checked="form.accessibleCategoryIds.includes(cat.id)"
                  class="sr-only"
                  @change="toggleCategory(cat.id)"
                />
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-medium text-slate-900">{{ cat.name }}</p>
                  <p v-if="cat.description" class="truncate text-xs text-slate-500">{{ cat.description }}</p>
                </div>
                <span
                  v-if="cat.type"
                  class="admin-pill shrink-0 bg-slate-100 text-slate-500"
                >
                  {{ cat.type }}
                </span>
              </label>
            </div>
            <p class="mt-2 text-xs text-slate-400">
              {{ form.accessibleCategoryIds.length }} of {{ categories.length }} topics selected
            </p>
          </div>

          <!-- Chatbot Access -->
          <div class="border-t border-slate-100 px-4 py-3">
            <FormField label="AI Chatbot Access" helpText="When available, subscribers get AI chatbot access">
              <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 p-3 transition-colors hover:bg-slate-50" :class="form.chatbotAccess ? 'border-blue-200 bg-blue-50' : ''">
                <input v-model="form.chatbotAccess" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                <div>
                  <p class="text-sm font-medium text-slate-900">Enable AI Chatbot</p>
                  <p class="text-xs text-slate-500">Grant access to the AI legal assistant (coming soon)</p>
                </div>
              </label>
            </FormField>
          </div>

          <!-- Active toggle -->
          <div class="border-t border-slate-100 px-4 py-3">
            <label class="flex cursor-pointer items-center gap-3">
              <input v-model="form.isActive" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
              <div>
                <p class="text-sm font-medium text-slate-900">Active</p>
                <p class="text-xs text-slate-500">Inactive packages won't appear when assigning to members</p>
              </div>
            </label>
          </div>

          <div class="flex justify-end border-t border-slate-100 px-4 py-3">
            <button
              :disabled="saving"
              class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-50"
              @click="save"
            >
              {{ saving ? "Saving..." : isNew ? "Create Package" : "Save Changes" }}
            </button>
          </div>
        </article>
      </template>
    </div>
  </AdminLayout>
</template>
