<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { RouterLink } from "vue-router";
import {
  Bot,
  MessageSquare,
  BookOpen,
  Sliders,
  MessageCircle,
  Shield,
  AlertTriangle,
  Send,
  Trash2,
  RefreshCw,
  Save,
  Loader2,
} from "lucide-vue-next";
import AdminLayout from "@/layouts/AdminLayout.vue";
import FormField from "@/components/FormField.vue";
import { useToast } from "@/composables/useToast";
import { getChatbotSettings, updateChatbotSettings, listCategories, listPackages } from "@/api/cms";
import type { Category, Package, ChatbotTestMessage } from "@/types";

const toast = useToast();

// ── State ──────────────────────────────────────────────────────────────────

const loading = ref(true);
const saving = ref(false);
const categories = ref<Category[]>([]);
const packages = ref<Package[]>([]);

const form = ref({
  chatbotEnabled: false,
  chatbotName: "LexSZA AI",
  chatbotWelcomeMessage: "Hi! I'm your AI legal assistant. Ask me anything about Malaysian law.",
  chatbotSystemPrompt:
    "You are a helpful legal assistant for Malaysian law students at UNISZA. Provide accurate, evidence-based answers about Malaysian law, contracts, constitutional law, and related subjects. Always cite the specific statute, case law, or learning material you are referencing. Be formal but approachable. If you do not have information on a topic, say so explicitly rather than guessing.",
  chatbotTemperature: 0.3,
  chatbotMaxResponseLength: "512",
  chatbotCitationMode: true,
  chatbotCitationFormat: "[Source: {topic} - {note}]",
  chatbotKnowledgeBaseTopicIds: [] as number[],
});

// ── Test Chat ──────────────────────────────────────────────────────────────

const testMessages = ref<ChatbotTestMessage[]>([]);
const testInput = ref("");

function clearTestHistory() {
  testMessages.value = [];
}

// ── Computed ───────────────────────────────────────────────────────────────

const selectedTopicCount = computed(() => form.value.chatbotKnowledgeBaseTopicIds.length);
const totalTopicCount = computed(() => categories.value.length);

const packagesWithChatbot = computed(() => packages.value.filter((p) => p.chatbotAccess));

// ── Knowledge Base toggles ─────────────────────────────────────────────────

function toggleTopic(id: number) {
  const idx = form.value.chatbotKnowledgeBaseTopicIds.indexOf(id);
  if (idx === -1) {
    form.value.chatbotKnowledgeBaseTopicIds.push(id);
  } else {
    form.value.chatbotKnowledgeBaseTopicIds.splice(idx, 1);
  }
}

function isTopicSelected(id: number) {
  return form.value.chatbotKnowledgeBaseTopicIds.includes(id);
}

// ── Load ───────────────────────────────────────────────────────────────────

onMounted(async () => {
  try {
    const [settingsRes, catRes, pkgRes] = await Promise.all([
      getChatbotSettings(),
      listCategories("?limit=200"),
      listPackages("?limit=100"),
    ]);

    categories.value = catRes.data ?? [];
    packages.value = pkgRes.data ?? [];

    // Populate form from saved settings (if keys exist)
    const s = settingsRes.data as Record<string, unknown>;
    if (s) {
      if (s.chatbotEnabled !== undefined) form.value.chatbotEnabled = s.chatbotEnabled === true || s.chatbotEnabled === "1" || s.chatbotEnabled === "true";
      if (s.chatbotName !== undefined) form.value.chatbotName = String(s.chatbotName);
      if (s.chatbotWelcomeMessage !== undefined) form.value.chatbotWelcomeMessage = String(s.chatbotWelcomeMessage);
      if (s.chatbotSystemPrompt !== undefined) form.value.chatbotSystemPrompt = String(s.chatbotSystemPrompt);
      if (s.chatbotTemperature !== undefined) form.value.chatbotTemperature = Number(s.chatbotTemperature);
      if (s.chatbotMaxResponseLength !== undefined) form.value.chatbotMaxResponseLength = String(s.chatbotMaxResponseLength);
      if (s.chatbotCitationMode !== undefined) form.value.chatbotCitationMode = s.chatbotCitationMode === true || s.chatbotCitationMode === "1" || s.chatbotCitationMode === "true";
      if (s.chatbotCitationFormat !== undefined) form.value.chatbotCitationFormat = String(s.chatbotCitationFormat);
      if (s.chatbotKnowledgeBaseTopicIds !== undefined) {
        try {
          const parsed = typeof s.chatbotKnowledgeBaseTopicIds === "string"
            ? JSON.parse(s.chatbotKnowledgeBaseTopicIds)
            : s.chatbotKnowledgeBaseTopicIds;
          if (Array.isArray(parsed)) form.value.chatbotKnowledgeBaseTopicIds = parsed.map(Number);
        } catch {
          // keep default
        }
      }
    }
  } catch (err) {
    toast.error("Failed to load chatbot settings");
    console.error(err);
  } finally {
    loading.value = false;
  }
});

// ── Save ───────────────────────────────────────────────────────────────────

async function save() {
  saving.value = true;
  try {
    await updateChatbotSettings({
      chatbotEnabled: form.value.chatbotEnabled,
      chatbotName: form.value.chatbotName,
      chatbotWelcomeMessage: form.value.chatbotWelcomeMessage,
      chatbotSystemPrompt: form.value.chatbotSystemPrompt,
      chatbotTemperature: form.value.chatbotTemperature,
      chatbotMaxResponseLength: form.value.chatbotMaxResponseLength,
      chatbotCitationMode: form.value.chatbotCitationMode,
      chatbotCitationFormat: form.value.chatbotCitationFormat,
      chatbotKnowledgeBaseTopicIds: JSON.stringify(form.value.chatbotKnowledgeBaseTopicIds),
    });
    toast.success("Chatbot settings saved");
  } catch (err) {
    toast.error("Failed to save chatbot settings");
    console.error(err);
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <AdminLayout>
    <div class="max-w-3xl mx-auto space-y-6">
      <!-- Page header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-lg font-semibold text-slate-900">Chatbot</h1>
          <p class="text-sm text-slate-500">Configure the AI chatbot for your mobile app</p>
        </div>
        <button
          @click="save"
          :disabled="saving || loading"
          class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <Loader2 v-if="saving" class="h-4 w-4 animate-spin" />
          <Save v-else class="h-4 w-4" />
          {{ saving ? "Saving..." : "Save Settings" }}
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex items-center justify-center py-20">
        <Loader2 class="h-6 w-6 animate-spin text-slate-400" />
      </div>

      <template v-else>
        <!-- Section 1: Configuration -->
        <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3">
            <Bot class="h-4 w-4 text-slate-500" />
            <h2 class="text-sm font-semibold text-slate-900">Chatbot Configuration</h2>
          </div>
          <div class="p-5 space-y-4">
            <!-- Enable/Disable toggle -->
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-slate-700">Enable Chatbot</p>
                <p class="text-xs text-slate-400" :class="{ 'text-amber-500': !form.chatbotEnabled }">
                  {{ form.chatbotEnabled ? "Chatbot is active for users with access" : "Chatbot is disabled for all users" }}
                </p>
              </div>
              <button
                @click="form.chatbotEnabled = !form.chatbotEnabled"
                :class="[
                  'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out',
                  form.chatbotEnabled ? 'bg-slate-900' : 'bg-slate-200',
                ]"
                role="switch"
                :aria-checked="form.chatbotEnabled"
              >
                <span
                  :class="[
                    'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                    form.chatbotEnabled ? 'translate-x-5' : 'translate-x-0',
                  ]"
                />
              </button>
            </div>

            <FormField label="Chatbot Name" helpText="Displayed in the mobile app chat interface">
              <input
                v-model="form.chatbotName"
                type="text"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-400"
              />
            </FormField>

            <FormField label="Welcome Message" helpText="The first message students see when they open the chatbot">
              <textarea
                v-model="form.chatbotWelcomeMessage"
                rows="3"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-400"
              />
            </FormField>
          </div>
        </div>

        <!-- Section 2: System Prompt -->
        <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3">
            <MessageSquare class="h-4 w-4 text-slate-500" />
            <h2 class="text-sm font-semibold text-slate-900">System Prompt</h2>
          </div>
          <div class="p-5 space-y-3">
            <p class="text-xs text-slate-400">
              This instruction tells the AI how to behave and respond. Be specific about tone, scope, and limitations.
            </p>
            <textarea
              v-model="form.chatbotSystemPrompt"
              rows="12"
              class="w-full rounded-lg border border-slate-200 px-3 py-2 font-mono text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-400"
            />
            <p class="text-right text-xs text-slate-400">
              {{ form.chatbotSystemPrompt.length }} characters
            </p>
          </div>
        </div>

        <!-- Section 3: Knowledge Base -->
        <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3">
            <BookOpen class="h-4 w-4 text-slate-500" />
            <h2 class="text-sm font-semibold text-slate-900">Knowledge Base</h2>
          </div>
          <div class="p-5 space-y-4">
            <p class="text-xs text-slate-400">
              The chatbot uses your uploaded Notes as its knowledge source. Select which topics to include.
            </p>

            <div v-if="categories.length === 0" class="text-sm text-slate-400 py-4 text-center">
              No topics found. Create categories first.
            </div>
            <div v-else class="divide-y divide-slate-100 rounded-lg border border-slate-200">
              <label
                v-for="cat in categories"
                :key="cat.id"
                class="flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-slate-50 transition-colors"
              >
                <input
                  type="checkbox"
                  :checked="isTopicSelected(cat.id)"
                  @change="toggleTopic(cat.id)"
                  class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                />
                <span class="flex-1 text-sm text-slate-700">{{ cat.name }}</span>
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                  {{ cat._count?.notes ?? 0 }} notes
                </span>
              </label>
            </div>

            <div class="flex items-center justify-between pt-2">
              <p class="text-xs text-slate-500">
                {{ selectedTopicCount }} of {{ totalTopicCount }} topics included
              </p>
              <div class="flex items-center gap-3">
                <span class="text-xs text-slate-400">Last indexed: Not yet configured</span>
                <button
                  disabled
                  class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-400 cursor-not-allowed"
                  title="Available after LLM provider is configured"
                >
                  <RefreshCw class="h-3 w-3" />
                  Re-index All
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Section 4: Response Settings -->
        <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3">
            <Sliders class="h-4 w-4 text-slate-500" />
            <h2 class="text-sm font-semibold text-slate-900">Response Settings</h2>
          </div>
          <div class="p-5 space-y-5">
            <!-- Temperature -->
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <label class="text-xs font-medium text-slate-700">Temperature</label>
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">
                  {{ form.chatbotTemperature.toFixed(1) }}
                </span>
              </div>
              <input
                v-model.number="form.chatbotTemperature"
                type="range"
                min="0"
                max="1"
                step="0.1"
                class="w-full accent-slate-900"
              />
              <div class="flex justify-between text-xs text-slate-400">
                <span>Precise</span>
                <span>Creative</span>
              </div>
            </div>

            <!-- Max Response Length -->
            <FormField label="Max Response Length">
              <select
                v-model="form.chatbotMaxResponseLength"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-400"
              >
                <option value="256">Short (256 tokens)</option>
                <option value="512">Medium (512 tokens)</option>
                <option value="1024">Long (1024 tokens)</option>
              </select>
            </FormField>

            <!-- Citation Mode -->
            <div class="flex items-center gap-3">
              <input
                v-model="form.chatbotCitationMode"
                type="checkbox"
                id="citationMode"
                class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
              />
              <label for="citationMode" class="text-sm text-slate-700 cursor-pointer">
                Always cite sources when answering
              </label>
            </div>

            <!-- Citation Format -->
            <FormField label="Citation Format" helpText="Use {topic} and {note} as placeholders for the source topic and note title">
              <input
                v-model="form.chatbotCitationFormat"
                type="text"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-400"
              />
            </FormField>
          </div>
        </div>

        <!-- Section 5: Test Chat -->
        <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
            <div class="flex items-center gap-2">
              <MessageCircle class="h-4 w-4 text-slate-500" />
              <h2 class="text-sm font-semibold text-slate-900">Test Chat</h2>
            </div>
            <button
              v-if="testMessages.length > 0"
              @click="clearTestHistory"
              class="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-slate-600 transition-colors"
            >
              <Trash2 class="h-3 w-3" />
              Clear history
            </button>
          </div>
          <div class="p-5 space-y-4">
            <!-- Warning banner -->
            <div class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3">
              <AlertTriangle class="h-4 w-4 shrink-0 text-amber-500 mt-0.5" />
              <p class="text-xs text-amber-700">
                LLM provider has not been configured yet. Connect a provider in Settings to enable live testing.
              </p>
            </div>

            <!-- Messages area -->
            <div class="max-h-96 overflow-y-auto space-y-3 min-h-[80px]">
              <div v-if="testMessages.length === 0" class="flex items-center justify-center py-8">
                <p class="text-xs text-slate-400">No test messages yet</p>
              </div>
              <template v-for="msg in testMessages" :key="msg.id">
                <!-- User message -->
                <div v-if="msg.role === 'user'" class="flex justify-end">
                  <div class="bg-slate-900 text-white rounded-2xl rounded-br-md px-4 py-2 max-w-[80%]">
                    <p class="text-sm">{{ msg.content }}</p>
                  </div>
                </div>
                <!-- Assistant message -->
                <div v-else class="flex justify-start">
                  <div>
                    <div class="bg-white border border-slate-200 rounded-2xl rounded-bl-md px-4 py-2 max-w-[80%]">
                      <p class="text-sm text-slate-900">{{ msg.content }}</p>
                    </div>
                    <div v-if="msg.citations && msg.citations.length > 0" class="mt-1 pl-2 space-y-0.5">
                      <p v-for="(cite, i) in msg.citations" :key="i" class="text-xs text-slate-400">
                        {{ cite }}
                      </p>
                    </div>
                  </div>
                </div>
              </template>
            </div>

            <!-- Input bar -->
            <div class="flex gap-2">
              <input
                v-model="testInput"
                type="text"
                placeholder="Type a test message..."
                disabled
                class="flex-1 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-400 disabled:bg-slate-50 disabled:cursor-not-allowed"
              />
              <button
                disabled
                class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-3 py-2 text-white opacity-50 cursor-not-allowed"
              >
                <Send class="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- Section 6: Access Control -->
        <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3">
            <Shield class="h-4 w-4 text-slate-500" />
            <h2 class="text-sm font-semibold text-slate-900">Access Control</h2>
          </div>
          <div class="p-5 space-y-4">
            <p class="text-xs text-slate-400">
              Manage which subscription packages include chatbot access.
            </p>

            <div v-if="packages.length === 0" class="text-sm text-slate-400 py-4 text-center">
              No packages found.
            </div>
            <table v-else class="w-full text-sm">
              <thead>
                <tr class="border-b border-slate-100">
                  <th class="py-2 pr-4 text-left text-xs font-medium text-slate-500">Package Name</th>
                  <th class="py-2 pr-4 text-left text-xs font-medium text-slate-500">Chatbot Access</th>
                  <th class="py-2 text-left text-xs font-medium text-slate-500">Subscribers</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50">
                <tr v-for="pkg in packages" :key="pkg.id">
                  <td class="py-2 pr-4 text-slate-700">{{ pkg.name }}</td>
                  <td class="py-2 pr-4">
                    <span
                      :class="[
                        'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                        pkg.chatbotAccess
                          ? 'bg-emerald-50 text-emerald-700'
                          : 'bg-slate-100 text-slate-500',
                      ]"
                    >
                      {{ pkg.chatbotAccess ? "Yes" : "No" }}
                    </span>
                  </td>
                  <td class="py-2 text-slate-500">{{ pkg.subscriptionsCount ?? 0 }}</td>
                </tr>
              </tbody>
            </table>

            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
              <p class="text-xs text-slate-500">
                {{ packagesWithChatbot.length }} of {{ packages.length }} packages include chatbot access
              </p>
              <RouterLink
                to="/admin/packages"
                class="text-xs font-medium text-slate-600 hover:text-slate-900 transition-colors"
              >
                Manage Packages &rarr;
              </RouterLink>
            </div>
          </div>
        </div>
      </template>
    </div>
  </AdminLayout>
</template>
