import { apiRequest } from "./client";
import type {
  AuditLog,
  CaseSummary,
  CaseSummaryInput,
  Category,
  CategoryInput,
  DashboardSummary,
  FreeLink,
  FreeLinkInput,
  Media,
  MediaMetadataInput,
  MemberDetail,
  MemberInput,
  MemberSubscription,
  MemberSubscriptionInput,
  Note,
  Package,
  PackageInput,
  Question,
  QuestionInput,
  QuestionPaper,
  QuestionPaperInput,
  Role,
  RoleInput,
  SettingsPayload,
  Statute,
  StatuteInput,
  TopicLink,
  TopicLinkInput,
  UserDetail,
  UserInput,
} from "@/types";

// ─── Dashboard ───────────────────────────────────────────────────────────────

export async function fetchDashboardSummary() {
  return apiRequest<{ data: DashboardSummary }>("/api/dashboard/summary");
}

// ─── Categories / Topics ─────────────────────────────────────────────────────

export async function listCategories(params = "") {
  return apiRequest<{ data: Category[]; meta: Record<string, unknown> }>(`/api/categories${params}`);
}

export async function getCategory(id: number) {
  return apiRequest<{ data: Category }>(`/api/categories/${id}`);
}

export async function createCategory(input: CategoryInput) {
  return apiRequest<{ data: Category }>("/api/categories", { method: "POST", body: JSON.stringify(input) });
}

export async function updateCategory(id: number, input: CategoryInput) {
  return apiRequest<{ data: Category }>(`/api/categories/${id}`, { method: "PUT", body: JSON.stringify(input) });
}

export async function deleteCategory(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/categories/${id}`, { method: "DELETE" });
}

// ─── Topic Links ─────────────────────────────────────────────────────────────

export async function listTopicLinks(categoryId: number) {
  return apiRequest<{ data: TopicLink[] }>(`/api/topic-links?category_id=${categoryId}`);
}

export async function createTopicLink(input: TopicLinkInput) {
  return apiRequest<{ data: TopicLink }>("/api/topic-links", { method: "POST", body: JSON.stringify(input) });
}

export async function updateTopicLink(id: number, input: Partial<TopicLinkInput>) {
  return apiRequest<{ data: TopicLink }>(`/api/topic-links/${id}`, { method: "PUT", body: JSON.stringify(input) });
}

export async function deleteTopicLink(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/topic-links/${id}`, { method: "DELETE" });
}

// ─── Notes ───────────────────────────────────────────────────────────────────

export async function listNotes(params = "") {
  return apiRequest<{ data: Note[]; meta: Record<string, unknown> }>(`/api/notes${params}`);
}

export async function getNote(id: number) {
  return apiRequest<{ data: Note }>(`/api/notes/${id}`);
}

export async function createNote(formData: FormData) {
  return apiRequest<{ data: Note }>("/api/notes", { method: "POST", body: formData });
}

export async function updateNote(id: number, input: Partial<Note>) {
  return apiRequest<{ data: Note }>(`/api/notes/${id}`, { method: "PUT", body: JSON.stringify(input) });
}

export async function deleteNote(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/notes/${id}`, { method: "DELETE" });
}

export async function uploadNoteFile(noteId: number, file: File) {
  const formData = new FormData();
  formData.append("file", file);
  return apiRequest<{ data: Note }>(`/api/notes/${noteId}/upload`, { method: "POST", body: formData });
}

// ─── Case Summaries ──────────────────────────────────────────────────────────

export async function listCaseSummaries(params = "") {
  return apiRequest<{ data: CaseSummary[]; meta: Record<string, unknown> }>(`/api/case-summaries${params}`);
}

export async function getCaseSummary(id: number) {
  return apiRequest<{ data: CaseSummary }>(`/api/case-summaries/${id}`);
}

export async function createCaseSummary(input: CaseSummaryInput | FormData) {
  const body = input instanceof FormData ? input : JSON.stringify(input);
  return apiRequest<{ data: CaseSummary }>("/api/case-summaries", { method: "POST", body });
}

export async function updateCaseSummary(id: number, input: Partial<CaseSummaryInput> | FormData) {
  const body = input instanceof FormData ? input : JSON.stringify(input);
  return apiRequest<{ data: CaseSummary }>(`/api/case-summaries/${id}`, { method: "PUT", body });
}

export async function deleteCaseSummary(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/case-summaries/${id}`, { method: "DELETE" });
}

// ─── Questions ───────────────────────────────────────────────────────────────

export async function listQuestions(params = "") {
  return apiRequest<{ data: Question[]; meta: Record<string, unknown> }>(`/api/questions${params}`);
}

export async function getQuestion(id: number) {
  return apiRequest<{ data: Question }>(`/api/questions/${id}`);
}

export async function createQuestion(input: QuestionInput) {
  return apiRequest<{ data: Question }>("/api/questions", { method: "POST", body: JSON.stringify(input) });
}

export async function updateQuestion(id: number, input: Partial<QuestionInput>) {
  return apiRequest<{ data: Question }>(`/api/questions/${id}`, { method: "PUT", body: JSON.stringify(input) });
}

export async function deleteQuestion(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/questions/${id}`, { method: "DELETE" });
}

// ─── Question Papers ─────────────────────────────────────────────────────

export async function listQuestionPapers(params = "") {
  return apiRequest<{ data: QuestionPaper[]; meta: Record<string, unknown> }>(`/api/question-papers${params}`);
}

export async function getQuestionPaper(id: number) {
  return apiRequest<{ data: QuestionPaper }>(`/api/question-papers/${id}`);
}

export async function createQuestionPaper(formData: FormData) {
  return apiRequest<{ data: QuestionPaper }>("/api/question-papers", { method: "POST", body: formData });
}

export async function updateQuestionPaper(id: number, input: Partial<QuestionPaperInput>) {
  return apiRequest<{ data: QuestionPaper }>(`/api/question-papers/${id}`, { method: "PUT", body: JSON.stringify(input) });
}

export async function deleteQuestionPaper(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/question-papers/${id}`, { method: "DELETE" });
}

export async function uploadQuestionPaperFile(id: number, file: File) {
  const formData = new FormData();
  formData.append("file", file);
  return apiRequest<{ data: QuestionPaper }>(`/api/question-papers/${id}/upload`, { method: "POST", body: formData });
}

// ─── Statutes ────────────────────────────────────────────────────────────

export async function listStatutes(params = "") {
  return apiRequest<{ data: Statute[]; meta: Record<string, unknown> }>(`/api/statutes${params}`);
}

export async function getStatute(id: number) {
  return apiRequest<{ data: Statute }>(`/api/statutes/${id}`);
}

export async function createStatute(input: StatuteInput | FormData) {
  const body = input instanceof FormData ? input : JSON.stringify(input);
  return apiRequest<{ data: Statute }>("/api/statutes", { method: "POST", body });
}

export async function updateStatute(id: number, input: Partial<StatuteInput> | FormData) {
  const body = input instanceof FormData ? input : JSON.stringify(input);
  return apiRequest<{ data: Statute }>(`/api/statutes/${id}`, { method: "PUT", body });
}

export async function deleteStatute(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/statutes/${id}`, { method: "DELETE" });
}

// ─── Free Links (Case Law) ──────────────────────────────────────────────────

export async function listFreeLinks(params = "") {
  return apiRequest<{ data: FreeLink[]; meta: Record<string, unknown> }>(`/api/free-links${params}`);
}

export async function getFreeLink(id: number) {
  return apiRequest<{ data: FreeLink }>(`/api/free-links/${id}`);
}

export async function createFreeLink(input: FreeLinkInput) {
  return apiRequest<{ data: FreeLink }>("/api/free-links", { method: "POST", body: JSON.stringify(input) });
}

export async function updateFreeLink(id: number, input: Partial<FreeLinkInput>) {
  return apiRequest<{ data: FreeLink }>(`/api/free-links/${id}`, { method: "PUT", body: JSON.stringify(input) });
}

export async function deleteFreeLink(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/free-links/${id}`, { method: "DELETE" });
}

// ─── Media ───────────────────────────────────────────────────────────────────

export async function listMedia() {
  return apiRequest<{ data: Media[] }>("/api/media");
}

export async function uploadMedia(file: File) {
  const formData = new FormData();
  formData.append("file", file);
  return apiRequest<{ data: Media }>("/api/media/upload", { method: "POST", body: formData });
}

export async function removeMedia(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/media/${id}`, { method: "DELETE" });
}

export async function updateMediaMetadata(id: number, input: MediaMetadataInput) {
  return apiRequest<{ data: Media }>(`/api/media/${id}`, { method: "PUT", body: JSON.stringify(input) });
}

// ─── Settings ────────────────────────────────────────────────────────────────

export async function getSettings() {
  return apiRequest<{ data: SettingsPayload }>("/api/settings");
}

export async function updateSettings(payload: Partial<SettingsPayload>) {
  return apiRequest<{ data: SettingsPayload }>("/api/settings", {
    method: "PUT",
    body: JSON.stringify(payload),
  });
}

// ─── Admin Users ─────────────────────────────────────────────────────────────

export async function listUsers(params = "") {
  return apiRequest<{ data: UserDetail[]; meta: Record<string, unknown> }>(`/api/users${params}`);
}

export async function getUser(id: number) {
  return apiRequest<{ data: UserDetail }>(`/api/users/${id}`);
}

export async function createUser(input: UserInput) {
  return apiRequest<{ data: UserDetail }>("/api/users", { method: "POST", body: JSON.stringify(input) });
}

export async function updateUser(id: number, input: Partial<UserInput>) {
  return apiRequest<{ data: UserDetail }>(`/api/users/${id}`, { method: "PUT", body: JSON.stringify(input) });
}

export async function deleteUser(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/users/${id}`, { method: "DELETE" });
}

export async function updateUserStatus(id: number, status: "active" | "suspended") {
  return apiRequest<{ data: UserDetail }>(`/api/users/${id}/status`, {
    method: "PUT",
    body: JSON.stringify({ status }),
  });
}

// ─── Members (mobile app users) ──────────────────────────────────────────────

export async function listMembers(params = "") {
  return apiRequest<{ data: MemberDetail[]; meta: Record<string, unknown> }>(`/api/members${params}`);
}

export async function getMember(id: number) {
  return apiRequest<{ data: MemberDetail }>(`/api/members/${id}`);
}

export async function createMember(input: MemberInput) {
  return apiRequest<{ data: MemberDetail }>("/api/members", { method: "POST", body: JSON.stringify(input) });
}

export async function updateMember(id: number, input: Partial<MemberInput>) {
  return apiRequest<{ data: MemberDetail }>(`/api/members/${id}`, { method: "PUT", body: JSON.stringify(input) });
}

export async function deleteMember(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/members/${id}`, { method: "DELETE" });
}

export async function updateMemberStatus(id: number, status: "active" | "suspended") {
  return apiRequest<{ data: MemberDetail }>(`/api/members/${id}/status`, {
    method: "PUT",
    body: JSON.stringify({ status }),
  });
}

// ─── Member Subscriptions ────────────────────────────────────────────────────

export async function getMemberSubscription(memberId: number) {
  return apiRequest<{ data: MemberSubscription | null }>(`/api/members/${memberId}/subscription`);
}

export async function assignMemberSubscription(memberId: number, input: MemberSubscriptionInput) {
  return apiRequest<{ data: MemberSubscription }>(`/api/members/${memberId}/subscription`, {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export async function removeMemberSubscription(memberId: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/members/${memberId}/subscription`, { method: "DELETE" });
}

// ─── Subscription Packages ───────────────────────────────────────────────────

export async function listPackages(params = "") {
  return apiRequest<{ data: Package[]; meta: Record<string, unknown> }>(`/api/packages${params}`);
}

export async function getPackage(id: number) {
  return apiRequest<{ data: Package }>(`/api/packages/${id}`);
}

export async function createPackage(input: PackageInput) {
  return apiRequest<{ data: Package }>("/api/packages", { method: "POST", body: JSON.stringify(input) });
}

export async function updatePackage(id: number, input: Partial<PackageInput>) {
  return apiRequest<{ data: Package }>(`/api/packages/${id}`, { method: "PUT", body: JSON.stringify(input) });
}

export async function deletePackage(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/packages/${id}`, { method: "DELETE" });
}

// ─── Roles & Permissions ─────────────────────────────────────────────────────

export async function listPermissions() {
  return apiRequest<{ data: string[] }>("/api/permissions");
}

export async function listRoles() {
  return apiRequest<{ data: Role[] }>("/api/roles");
}

export async function getRole(id: number) {
  return apiRequest<{ data: Role }>(`/api/roles/${id}`);
}

export async function createRole(input: RoleInput) {
  return apiRequest<{ data: Role }>("/api/roles", { method: "POST", body: JSON.stringify(input) });
}

export async function updateRole(id: number, input: RoleInput) {
  return apiRequest<{ data: Role }>(`/api/roles/${id}`, { method: "PUT", body: JSON.stringify(input) });
}

export async function deleteRole(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/roles/${id}`, { method: "DELETE" });
}

// ── Chatbot Settings ────────────────────────────────────────────────────────
export async function getChatbotSettings() {
  return getSettings();
}
export async function updateChatbotSettings(input: Record<string, unknown>) {
  return updateSettings(input as Partial<SettingsPayload>);
}

// ─── Audit Logs ──────────────────────────────────────────────────────────────

export async function listAuditLogs(params = "") {
  return apiRequest<{ data: AuditLog[]; meta: Record<string, unknown> }>(`/api/audit-logs${params}`);
}

// ─── Dev Console (remove before production) ──────────────────────────────────

export async function devInfo() {
  return apiRequest<{ data: Record<string, unknown> }>("/api/dev/info");
}

export async function devTables() {
  return apiRequest<{ data: { table: string; count: number }[] }>("/api/dev/tables");
}

export async function devTableRows(table: string, params = "") {
  return apiRequest<{ data: unknown[]; meta: Record<string, unknown> }>(`/api/dev/tables/${table}${params}`);
}

export async function devCreateRow(table: string, data: Record<string, unknown>) {
  return apiRequest<{ data: unknown }>(`/api/dev/tables/${table}`, { method: "POST", body: JSON.stringify(data) });
}

export async function devUpdateRow(table: string, id: number, data: Record<string, unknown>) {
  return apiRequest<{ data: unknown }>(`/api/dev/tables/${table}/${id}`, { method: "PUT", body: JSON.stringify(data) });
}

export async function devDeleteRow(table: string, id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/dev/tables/${table}/${id}`, { method: "DELETE" });
}
