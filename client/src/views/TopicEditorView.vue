<script setup lang="ts">
import { onMounted, ref, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
  ArrowLeft,
  FileText,
  Scale,
  HelpCircle,
  Link as LinkIcon,
  Upload,
  Plus,
  Trash2,
  Pencil,
  ExternalLink,
} from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import FormField from "@/components/FormField.vue";
import LoadingSkeleton from "@/components/LoadingSkeleton.vue";
import EmptyState from "@/components/EmptyState.vue";
import { useFormDirty } from "@/composables/useFormDirty";
import { useFieldValidation } from "@/composables/useFieldValidation";
import {
  getCategory,
  createCategory,
  updateCategory,
  listNotes,
  deleteNote,
  listCaseSummaries,
  updateCaseSummary,
  listQuestions,
  deleteQuestion,
  listTopicLinks,
  createTopicLink,
  updateTopicLink,
  deleteTopicLink,
} from "@/api/cms";
import { useToast } from "@/composables/useToast";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import type { Category, Note, CaseSummary, Question, TopicLink } from "@/types";

const route = useRoute();
const router = useRouter();
const toast = useToast();
const confirmDialog = useConfirmDialog();

const isNew = computed(() => !route.params.id || route.params.id === "new");
const topicId = computed(() => (isNew.value ? null : Number(route.params.id)));

type Tab = "details" | "notes" | "cases" | "questions" | "links";
const activeTab = ref<Tab>("details");

const form = ref({
  name: "",
  slug: "",
  description: "",
  legalBasis: "",
  iconUrl: "",
  sortOrder: 0,
});
const saving = ref(false);

const { isDirty, resetDirty } = useFormDirty(form);
const { errors, validateAll, clearError } = useFieldValidation({
  name: { required: true, message: "Topic name is required" },
});

const notes = ref<Note[]>([]);
const notesLoading = ref(false);

const cases = ref<CaseSummary[]>([]);
const allCases = ref<CaseSummary[]>([]);
const casesLoading = ref(false);
const showCasePicker = ref(false);
const caseSearch = ref("");

const questions = ref<Question[]>([]);
const questionsLoading = ref(false);

const links = ref<TopicLink[]>([]);
const linksLoading = ref(false);
const editingLink = ref<TopicLink | null>(null);
const linkForm = ref({ title: "", url: "", sortOrder: 0, isActive: true });
const showLinkForm = ref(false);
const savingLink = ref(false);


async function load() {
  if (!isNew.value && topicId.value) {
    const res = await getCategory(topicId.value);
    const c: Category = res.data;
    form.value = {
      name: c.name,
      slug: c.slug,
      description: c.description ?? "",
      legalBasis: c.legalBasis ?? "",
      iconUrl: c.iconUrl ?? "",
      sortOrder: c.sortOrder,
    };
    await Promise.all([loadNotes(), loadCases(), loadQuestions(), loadLinks()]);
  }
}

async function loadNotes() {
  if (!topicId.value) return;
  notesLoading.value = true;
  try { const res = await listNotes(`?category_id=${topicId.value}&limit=100`); notes.value = res.data; }
  finally { notesLoading.value = false; }
}

async function loadCases() {
  if (!topicId.value) return;
  casesLoading.value = true;
  try { const res = await listCaseSummaries(`?category_id=${topicId.value}&limit=100`); cases.value = res.data; }
  finally { casesLoading.value = false; }
}

async function loadAllCases() {
  const res = await listCaseSummaries("?limit=200&sort_by=title");
  allCases.value = res.data;
}

async function loadQuestions() {
  if (!topicId.value) return;
  questionsLoading.value = true;
  try { const res = await listQuestions(`?category_id=${topicId.value}&limit=200`); questions.value = res.data; }
  finally { questionsLoading.value = false; }
}

async function loadLinks() {
  if (!topicId.value) return;
  linksLoading.value = true;
  try { const res = await listTopicLinks(topicId.value); links.value = res.data; }
  finally { linksLoading.value = false; }
}

async function save() {
  if (!validateAll({ name: form.value.name })) return;
  saving.value = true;
  try {
    const payload = {
      name: form.value.name,
      slug: form.value.slug || undefined,
      description: form.value.description || undefined,
      legalBasis: form.value.legalBasis || undefined,
      iconUrl: form.value.iconUrl || undefined,
      sortOrder: form.value.sortOrder,
    };
    if (isNew.value) {
      const res = await createCategory(payload);
      toast.success("Topic created");
      router.push(`/admin/topics/${res.data.id}`);
    } else {
      await updateCategory(topicId.value!, payload);
      toast.success("Topic saved");
      resetDirty();
    }
  } catch (e) {
    toast.error("Save failed", e instanceof Error ? e.message : "");
  } finally {
    saving.value = false;
  }
}

async function removeNote(id: number) {
  const ok = await confirmDialog.confirm({ title: "Delete note?", message: "This cannot be undone.", confirmText: "Delete", destructive: true });
  if (!ok) return;
  try { await deleteNote(id); await loadNotes(); toast.success("Note deleted"); }
  catch { toast.error("Delete failed"); }
}

async function openCasePicker() {
  await loadAllCases();
  showCasePicker.value = true;
}

const filteredAllCases = computed(() => {
  const q = caseSearch.value.toLowerCase();
  return allCases.value.filter(
    (c) => !cases.value.find((e) => e.id === c.id) &&
      (c.title.toLowerCase().includes(q) || c.citation.toLowerCase().includes(q))
  );
});

async function assignCase(cs: CaseSummary) {
  if (!topicId.value) return;
  try { await updateCaseSummary(cs.id, { categoryId: topicId.value }); await loadCases(); toast.success(`"${cs.title}" assigned`); }
  catch { toast.error("Failed to assign case"); }
}

async function unassignCase(cs: CaseSummary) {
  const ok = await confirmDialog.confirm({ title: "Remove from topic?", message: `"${cs.title}" will be unlinked from this topic.`, confirmText: "Remove", destructive: true });
  if (!ok) return;
  try { await updateCaseSummary(cs.id, { categoryId: undefined }); await loadCases(); toast.success("Case removed"); }
  catch { toast.error("Failed to remove case"); }
}

async function removeQuestion(id: number) {
  const ok = await confirmDialog.confirm({ title: "Delete question?", message: "This cannot be undone.", confirmText: "Delete", destructive: true });
  if (!ok) return;
  try { await deleteQuestion(id); await loadQuestions(); toast.success("Question deleted"); }
  catch { toast.error("Delete failed"); }
}

function openNewLink() {
  editingLink.value = null;
  linkForm.value = { title: "", url: "", sortOrder: links.value.length, isActive: true };
  showLinkForm.value = true;
}

function openEditLink(link: TopicLink) {
  editingLink.value = link;
  linkForm.value = { title: link.title, url: link.url, sortOrder: link.sortOrder, isActive: link.isActive };
  showLinkForm.value = true;
}

async function saveLink() {
  if (!topicId.value) return;
  savingLink.value = true;
  try {
    if (editingLink.value) {
      await updateTopicLink(editingLink.value.id, linkForm.value);
    } else {
      await createTopicLink({ ...linkForm.value, categoryId: topicId.value });
    }
    await loadLinks();
    showLinkForm.value = false;
    toast.success(editingLink.value ? "Link updated" : "Link added");
  } catch (e) {
    toast.error("Save failed", e instanceof Error ? e.message : "");
  } finally {
    savingLink.value = false;
  }
}

async function removeLink(id: number) {
  const ok = await confirmDialog.confirm({ title: "Delete link?", message: "This cannot be undone.", confirmText: "Delete", destructive: true });
  if (!ok) return;
  try { await deleteTopicLink(id); await loadLinks(); toast.success("Link deleted"); }
  catch { toast.error("Delete failed"); }
}

onMounted(load);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-4xl space-y-6">
      <div class="space-y-3">
        <div class="flex items-center gap-3">
          <router-link to="/admin/topics" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm transition-colors hover:bg-slate-50 hover:text-slate-900">
            <ArrowLeft class="h-4 w-4" /> Topics
          </router-link>
          <div class="space-y-1">
            <p class="page-kicker">Content Taxonomy</p>
            <h1 class="page-title">{{ isNew ? "New Topic" : form.name || "Edit Topic" }}</h1>
          </div>
        </div>
        <p class="page-subtitle">Organise notes, case summaries, questions, and supporting links with clearer tabs and a roomier editing surface.</p>
      </div>

      <div v-if="!isNew" class="admin-tablist overflow-x-auto">
        <button
          v-for="tab in [
            { key: 'details', label: 'Details' },
            { key: 'notes', label: `Notes (${notes.length})` },
            { key: 'cases', label: `Cases (${cases.length})` },
            { key: 'questions', label: `Questions (${questions.length})` },
            { key: 'links', label: `Extra Links (${links.length})` },
          ]"
          :key="tab.key"
          class="admin-tab whitespace-nowrap"
          :class="activeTab === tab.key ? 'admin-tab-active' : ''"
          @click="activeTab = tab.key as Tab"
        >{{ tab.label }}</button>
      </div>

      <article v-show="activeTab === 'details' || isNew" class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-4">
          <h2 class="text-base font-semibold text-slate-950">Topic Details</h2>
        </div>
        <div class="grid gap-4 p-4 sm:grid-cols-2">
          <FormField label="Name" :required="true" :error="errors.name" class="sm:col-span-2">
            <input v-model="form.name" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" @input="clearError('name')" />
          </FormField>
          <FormField label="Description" class="sm:col-span-2">
            <textarea v-model="form.description" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" />
          </FormField>
          <FormField label="Legal Basis" help-text="Displayed in the mobile app under each topic as the legal basis reference." class="sm:col-span-2">
            <textarea v-model="form.legalBasis" rows="4" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="Enter the legal basis or statutory reference for this topic..." />
          </FormField>
          <FormField label="Icon URL" help-text="URL to icon displayed in the mobile app">
            <input v-model="form.iconUrl" type="url" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="https://…" />
          </FormField>
          <FormField label="Sort Order" help-text="Lower numbers appear first">
            <input v-model.number="form.sortOrder" type="number" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" />
          </FormField>
        </div>
        <div class="flex justify-end border-t border-slate-100 px-4 py-3">
          <button :disabled="saving" class="admin-primary-button" @click="save">
            {{ saving ? "Saving…" : isNew ? "Create Topic" : "Save Details" }}
          </button>
        </div>
      </article>

      <!-- Notes -->
      <article v-if="!isNew && activeTab === 'notes'" class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
          <div class="flex items-center gap-2"><FileText class="h-4 w-4 text-blue-600" /><h2 class="text-sm font-semibold text-slate-900">Notes</h2></div>
          <router-link :to="`/admin/notes/new?category=${topicId}`" class="admin-primary-button px-3 py-2 text-xs">
            <Upload class="h-3.5 w-3.5" /> Upload Note
          </router-link>
        </div>
        <table class="w-full text-sm">
          <thead><tr class="border-b border-slate-100 text-left">
            <th class="px-4 py-2 text-xs font-semibold text-slate-500">Title</th>
            <th class="px-4 py-2 text-xs font-semibold text-slate-500">File</th>
            <th class="px-4 py-2 text-xs font-semibold text-slate-500">Published</th>
            <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">Actions</th>
          </tr></thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="notesLoading"><td colspan="4"><LoadingSkeleton variant="table" :lines="3" /></td></tr>
            <tr v-for="note in notes" :key="note.id" class="hover:bg-slate-50">
              <td class="px-4 py-2 font-medium text-slate-900">{{ note.title }}</td>
              <td class="px-4 py-2 text-slate-500 text-xs"><span class="rounded bg-slate-100 px-1.5 py-0.5 uppercase font-mono">{{ note.fileType }}</span> {{ (note.fileSize/1024).toFixed(0) }}KB</td>
              <td class="px-4 py-2"><span :class="note.isPublished?'bg-emerald-100 text-emerald-700':'bg-slate-100 text-slate-500'" class="rounded-full px-2 py-0.5 text-xs font-medium">{{ note.isPublished?'Yes':'No' }}</span></td>
              <td class="px-4 py-2 text-right"><div class="flex items-center justify-end gap-1">
                <router-link :to="`/admin/notes/${note.id}`" class="flex h-7 w-7 items-center justify-center rounded text-slate-400 hover:bg-slate-100 hover:text-slate-700"><Pencil class="h-3.5 w-3.5" /></router-link>
                <button class="flex h-7 w-7 items-center justify-center rounded text-slate-400 hover:bg-rose-50 hover:text-rose-600" @click="removeNote(note.id)"><Trash2 class="h-3.5 w-3.5" /></button>
              </div></td>
            </tr>
            <tr v-if="!notesLoading && notes.length===0"><td colspan="4"><EmptyState :icon="FileText" title="No notes for this topic" description="Upload notes to make them available under this topic." action-label="Add Note" :action-to="`/admin/notes/new?category=${topicId}`" /></td></tr>
          </tbody>
        </table>
      </article>

      <!-- Cases -->
      <article v-if="!isNew && activeTab === 'cases'" class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
          <div class="flex items-center gap-2"><Scale class="h-4 w-4 text-blue-600" /><h2 class="text-sm font-semibold text-slate-900">Case Summaries</h2></div>
          <button class="admin-primary-button px-3 py-2 text-xs" @click="openCasePicker">
            <Plus class="h-3.5 w-3.5" /> Assign Case
          </button>
        </div>
        <table class="w-full text-sm">
          <thead><tr class="border-b border-slate-100 text-left">
            <th class="px-4 py-2 text-xs font-semibold text-slate-500">Title</th>
            <th class="px-4 py-2 text-xs font-semibold text-slate-500">Citation</th>
            <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">Actions</th>
          </tr></thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="casesLoading"><td colspan="3"><LoadingSkeleton variant="table" :lines="3" /></td></tr>
            <tr v-for="cs in cases" :key="cs.id" class="hover:bg-slate-50">
              <td class="px-4 py-2 font-medium text-slate-900">{{ cs.title }}</td>
              <td class="px-4 py-2 text-slate-500 font-mono text-xs">{{ cs.citation }}</td>
              <td class="px-4 py-2 text-right"><div class="flex items-center justify-end gap-1">
                <router-link :to="`/admin/case-summaries/${cs.id}`" class="flex h-7 w-7 items-center justify-center rounded text-slate-400 hover:bg-slate-100 hover:text-slate-700"><Pencil class="h-3.5 w-3.5" /></router-link>
                <button class="flex h-7 w-7 items-center justify-center rounded text-slate-400 hover:bg-rose-50 hover:text-rose-600" @click="unassignCase(cs)"><Trash2 class="h-3.5 w-3.5" /></button>
              </div></td>
            </tr>
            <tr v-if="!casesLoading && cases.length===0"><td colspan="3"><EmptyState :icon="Scale" title="No cases assigned" description="Click &quot;Assign Case&quot; to link existing case summaries to this topic." action-label="Assign Case" @action="openCasePicker" /></td></tr>
          </tbody>
        </table>
        <!-- Case picker -->
        <div v-if="showCasePicker" class="border-t border-slate-100 p-4 space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-medium text-slate-900">Assign an existing case</h3>
            <button class="text-xs text-slate-500 hover:text-slate-800" @click="showCasePicker=false">✕ Close</button>
          </div>
          <input v-model="caseSearch" type="text" placeholder="Search by title or citation…" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" />
          <div class="max-h-56 overflow-y-auto divide-y divide-slate-100 rounded-lg border border-slate-200">
            <div v-for="cs in filteredAllCases" :key="cs.id" class="flex items-center justify-between px-3 py-2.5 hover:bg-slate-50 cursor-pointer" @click="assignCase(cs); showCasePicker=false">
              <div><p class="text-sm font-medium text-slate-900">{{ cs.title }}</p><p class="text-xs text-slate-500 font-mono">{{ cs.citation }}</p></div>
              <Plus class="h-4 w-4 text-blue-600 shrink-0" />
            </div>
            <div v-if="filteredAllCases.length===0" class="px-3 py-4 text-center text-sm text-slate-400">
              {{ allCases.length===0 ? 'No case summaries in the system yet.' : 'No unassigned cases match your search.' }}
            </div>
          </div>
        </div>
      </article>

      <!-- Questions -->
      <article v-if="!isNew && activeTab === 'questions'" class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
          <div class="flex items-center gap-2"><HelpCircle class="h-4 w-4 text-blue-600" /><h2 class="text-sm font-semibold text-slate-900">Questions</h2></div>
          <router-link :to="`/admin/questions/new?category=${topicId}`" class="admin-primary-button px-3 py-2 text-xs">
            <Plus class="h-3.5 w-3.5" /> Add Question
          </router-link>
        </div>
        <table class="w-full text-sm">
          <thead><tr class="border-b border-slate-100 text-left">
            <th class="px-4 py-2 text-xs font-semibold text-slate-500">#</th>
            <th class="px-4 py-2 text-xs font-semibold text-slate-500">Question</th>
            <th class="px-4 py-2 text-xs font-semibold text-slate-500">Published</th>
            <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">Actions</th>
          </tr></thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="questionsLoading"><td colspan="4"><LoadingSkeleton variant="table" :lines="3" /></td></tr>
            <tr v-for="(q, i) in questions" :key="q.id" class="hover:bg-slate-50">
              <td class="px-4 py-2 text-slate-400 text-xs">{{ i+1 }}</td>
              <td class="px-4 py-2 text-slate-900 max-w-md truncate">{{ q.questionText }}</td>
              <td class="px-4 py-2"><span :class="q.isPublished?'bg-emerald-100 text-emerald-700':'bg-slate-100 text-slate-500'" class="rounded-full px-2 py-0.5 text-xs font-medium">{{ q.isPublished?'Yes':'No' }}</span></td>
              <td class="px-4 py-2 text-right"><div class="flex items-center justify-end gap-1">
                <router-link :to="`/admin/questions/${q.id}`" class="flex h-7 w-7 items-center justify-center rounded text-slate-400 hover:bg-slate-100 hover:text-slate-700"><Pencil class="h-3.5 w-3.5" /></router-link>
                <button class="flex h-7 w-7 items-center justify-center rounded text-slate-400 hover:bg-rose-50 hover:text-rose-600" @click="removeQuestion(q.id)"><Trash2 class="h-3.5 w-3.5" /></button>
              </div></td>
            </tr>
            <tr v-if="!questionsLoading && questions.length===0"><td colspan="4"><EmptyState :icon="HelpCircle" title="No questions for this topic" description="Add questions to build a question bank for this topic." action-label="Add Question" :action-to="`/admin/questions/new?category=${topicId}`" /></td></tr>
          </tbody>
        </table>
      </article>

      <!-- Extra Links -->
      <article v-if="!isNew && activeTab === 'links'" class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
          <div class="flex items-center gap-2"><LinkIcon class="h-4 w-4 text-blue-600" /><h2 class="text-sm font-semibold text-slate-900">Extra Links</h2></div>
          <button class="admin-primary-button px-3 py-2 text-xs" @click="openNewLink">
            <Plus class="h-3.5 w-3.5" /> Add Link
          </button>
        </div>
        <table class="w-full text-sm">
          <thead><tr class="border-b border-slate-100 text-left">
            <th class="px-4 py-2 text-xs font-semibold text-slate-500">Title</th>
            <th class="px-4 py-2 text-xs font-semibold text-slate-500">URL</th>
            <th class="px-4 py-2 text-xs font-semibold text-slate-500">Active</th>
            <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">Actions</th>
          </tr></thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="linksLoading"><td colspan="4"><LoadingSkeleton variant="table" :lines="3" /></td></tr>
            <tr v-for="link in links" :key="link.id" class="hover:bg-slate-50">
              <td class="px-4 py-2 font-medium text-slate-900">{{ link.title }}</td>
              <td class="px-4 py-2 text-xs"><a :href="link.url" target="_blank" class="inline-flex items-center gap-1 text-blue-600 hover:underline">{{ link.url.length>50?link.url.slice(0,50)+'…':link.url }}<ExternalLink class="h-3 w-3"/></a></td>
              <td class="px-4 py-2"><span :class="link.isActive?'bg-emerald-100 text-emerald-700':'bg-slate-100 text-slate-500'" class="rounded-full px-2 py-0.5 text-xs font-medium">{{ link.isActive?'Yes':'No' }}</span></td>
              <td class="px-4 py-2 text-right"><div class="flex items-center justify-end gap-1">
                <button class="flex h-7 w-7 items-center justify-center rounded text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="openEditLink(link)"><Pencil class="h-3.5 w-3.5" /></button>
                <button class="flex h-7 w-7 items-center justify-center rounded text-slate-400 hover:bg-rose-50 hover:text-rose-600" @click="removeLink(link.id)"><Trash2 class="h-3.5 w-3.5" /></button>
              </div></td>
            </tr>
            <tr v-if="!linksLoading && links.length===0"><td colspan="4"><EmptyState :icon="LinkIcon" title="No extra links yet" description="Add supporting links for this topic." action-label="Add Link" @action="openNewLink" /></td></tr>
          </tbody>
        </table>
        <div v-if="showLinkForm" class="border-t border-slate-100 p-4 space-y-3 bg-slate-50">
          <h3 class="text-sm font-medium text-slate-900">{{ editingLink ? "Edit Link" : "New Link" }}</h3>
          <div class="grid grid-cols-2 gap-3">
            <FormField label="Title" :required="true">
              <input v-model="linkForm.title" type="text" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" />
            </FormField>
            <FormField label="URL" :required="true" help-text="Full URL including https://">
              <input v-model="linkForm.url" type="url" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" placeholder="https://…" />
            </FormField>
            <FormField label="Sort Order">
              <input v-model.number="linkForm.sortOrder" type="number" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" />
            </FormField>
            <div class="flex items-end pb-1">
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="linkForm.isActive" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-600" />
                <span class="text-sm text-slate-700">Active</span>
              </label>
            </div>
          </div>
          <div class="flex justify-end gap-2">
            <button class="admin-secondary-button" @click="showLinkForm=false">Cancel</button>
            <button :disabled="savingLink" class="admin-primary-button" @click="saveLink">
              {{ savingLink ? "Saving…" : "Save Link" }}
            </button>
          </div>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
