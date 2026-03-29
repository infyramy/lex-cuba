import { createRouter, createWebHistory } from "vue-router";

import LoginView from "@/views/LoginView.vue";
import DashboardView from "@/views/DashboardView.vue";
import UsersView from "@/views/UsersView.vue";
import UserEditView from "@/views/UserEditView.vue";
import MembersListView from "@/views/MembersListView.vue";
import MemberEditView from "@/views/MemberEditView.vue";
import PackagesListView from "@/views/PackagesListView.vue";
import PackageEditorView from "@/views/PackageEditorView.vue";
import RolesView from "@/views/RolesView.vue";
import TopicsListView from "@/views/TopicsListView.vue";
import TopicEditorView from "@/views/TopicEditorView.vue";
import NotesListView from "@/views/NotesListView.vue";
import NoteEditorView from "@/views/NoteEditorView.vue";
import CaseSummariesListView from "@/views/CaseSummariesListView.vue";
import CaseSummaryEditorView from "@/views/CaseSummaryEditorView.vue";
import QuestionsListView from "@/views/QuestionsListView.vue";
import QuestionEditorView from "@/views/QuestionEditorView.vue";
import FreeLinksView from "@/views/FreeLinksView.vue";
import StatutesListView from "@/views/StatutesListView.vue";
import StatuteEditorView from "@/views/StatuteEditorView.vue";
import BusinessSettingsView from "@/views/BusinessSettingsView.vue";
import AuditLogsView from "@/views/AuditLogsView.vue";
import MediaLibraryView from "@/views/MediaLibraryView.vue";
import ApiReferenceView from "@/views/ApiReferenceView.vue";
import ChatbotSettingsView from "@/views/ChatbotSettingsView.vue";
import PaymentsView from "@/views/PaymentsView.vue";
import { useAuthStore } from "@/stores/auth";
import { useSiteStore } from "@/stores/site";

const router = createRouter({
  history: createWebHistory(),
  routes: [
    // Auth
    { path: "/admin/login", name: "login", component: LoginView, meta: { guestOnly: true, title: "Login" } },

    // Dashboard
    { path: "/admin", name: "dashboard", component: DashboardView, meta: { requiresAuth: true, title: "Dashboard" } },

    // Members (mobile app users)
    { path: "/admin/members", name: "members", component: MembersListView, meta: { requiresAuth: true, title: "Members" } },
    { path: "/admin/members/new", name: "member-create", component: MemberEditView, meta: { requiresAuth: true, title: "New Member" } },
    { path: "/admin/members/:id", name: "member-edit", component: MemberEditView, meta: { requiresAuth: true, title: "Edit Member" } },

    // Subscription Packages
    { path: "/admin/packages", name: "packages", component: PackagesListView, meta: { requiresAuth: true, title: "Packages" } },
    { path: "/admin/packages/new", name: "package-create", component: PackageEditorView, meta: { requiresAuth: true, title: "New Package" } },
    { path: "/admin/packages/:id", name: "package-edit", component: PackageEditorView, meta: { requiresAuth: true, title: "Edit Package" } },

    // Content Management — Topics
    { path: "/admin/topics", name: "topics", component: TopicsListView, meta: { requiresAuth: true, title: "Topics" } },
    { path: "/admin/topics/new", name: "topic-create", component: TopicEditorView, meta: { requiresAuth: true, title: "New Topic" } },
    { path: "/admin/topics/:id", name: "topic-edit", component: TopicEditorView, meta: { requiresAuth: true, title: "Edit Topic" } },

    // Content Management — Notes
    { path: "/admin/notes", name: "notes", component: NotesListView, meta: { requiresAuth: true, title: "Notes" } },
    { path: "/admin/notes/new", name: "note-create", component: NoteEditorView, meta: { requiresAuth: true, title: "Upload Note" } },
    { path: "/admin/notes/:id", name: "note-edit", component: NoteEditorView, meta: { requiresAuth: true, title: "Edit Note" } },

    // Content Management — Case Summaries
    { path: "/admin/case-summaries", name: "case-summaries", component: CaseSummariesListView, meta: { requiresAuth: true, title: "Case Summaries" } },
    { path: "/admin/case-summaries/new", name: "case-summary-create", component: CaseSummaryEditorView, meta: { requiresAuth: true, title: "New Case Summary" } },
    { path: "/admin/case-summaries/:id", name: "case-summary-edit", component: CaseSummaryEditorView, meta: { requiresAuth: true, title: "Edit Case Summary" } },

    // Content Management — Question Bank
    { path: "/admin/questions", name: "questions", component: QuestionsListView, meta: { requiresAuth: true, title: "Question Bank" } },
    { path: "/admin/questions/new", name: "question-create", component: QuestionEditorView, meta: { requiresAuth: true, title: "New Question" } },
    { path: "/admin/questions/:id", name: "question-edit", component: QuestionEditorView, meta: { requiresAuth: true, title: "Edit Question" } },

    // Content Management — Statutes
    { path: "/admin/statutes", name: "statutes", component: StatutesListView, meta: { requiresAuth: true, title: "Statutes" } },
    { path: "/admin/statutes/new", name: "statute-create", component: StatuteEditorView, meta: { requiresAuth: true, title: "New Statute" } },
    { path: "/admin/statutes/:id", name: "statute-edit", component: StatuteEditorView, meta: { requiresAuth: true, title: "Edit Statute" } },

    // App Configuration — Case Law
    { path: "/admin/free-links", name: "free-links", component: FreeLinksView, meta: { requiresAuth: true, title: "Case Law" } },

    // System
    { path: "/admin/users", name: "users", component: UsersView, meta: { requiresAuth: true, title: "Admin Users" } },
    { path: "/admin/users/new", name: "user-create", component: UserEditView, meta: { requiresAuth: true, title: "New Admin User" } },
    { path: "/admin/users/:id", name: "user-edit", component: UserEditView, meta: { requiresAuth: true, title: "Edit Admin User" } },
    { path: "/admin/settings", name: "settings", component: BusinessSettingsView, meta: { requiresAuth: true, title: "Settings" } },
    { path: "/admin/roles", name: "roles", component: RolesView, meta: { requiresAuth: true, title: "Roles & Permissions" } },
    { path: "/admin/audit-logs", name: "audit-logs", component: AuditLogsView, meta: { requiresAuth: true, title: "Audit Logs" } },
    { path: "/admin/api-reference", name: "api-reference", component: ApiReferenceView, meta: { requiresAuth: true, title: "API Reference" } },

    // Chatbot (coming soon)
    { path: "/admin/chatbot", name: "chatbot", component: ChatbotSettingsView, meta: { requiresAuth: true, title: "**AI Chatbot" } },

    // Payments (coming soon)
    { path: "/admin/payments", name: "payments", component: PaymentsView, meta: { requiresAuth: true, title: "**Payments" } },

    // Media (accessible but not in sidebar)
    { path: "/admin/media", name: "media", component: MediaLibraryView, meta: { requiresAuth: true, title: "Media Library" } },

    // Catch-all redirect to dashboard
    { path: "/:pathMatch(.*)*", redirect: "/admin" },
  ],
});

router.beforeEach(async (to) => {
  const auth = useAuthStore();
  await auth.initialize();

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: "login" };
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return { name: "dashboard" };
  }

  return true;
});

router.afterEach((to) => {
  const site = useSiteStore();
  const pageTitle = (to.meta.title as string) || "Admin";
  site.setDocumentTitle(pageTitle);
});

export default router;
