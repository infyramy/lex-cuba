export type ThemeColor = "violet" | "blue" | "green" | "red" | "black-white" | "grey";

export type ApiError = { error: { code: string; message: string; details?: unknown } };

export type ApiResponse<T> = { data: T; meta?: Record<string, unknown> };

// Auth user (minimal, for session)
export type User = {
  id: number;
  email: string;
  name: string;
  photoUrl?: string;
  role?: string;
};

// ─── Admin Users ────────────────────────────────────────────────────────────

export type UserStatus = "active" | "suspended";
export type WorkStudyStatus = "working" | "studying";

export type UserDetail = {
  id: number;
  name: string;
  email: string;
  role: string;
  isActive: boolean;
  gender: string | null;
  phone: string | null;
  institution: string | null;
  workStudyStatus: WorkStudyStatus | null;
  country: string | null;
  status: UserStatus;
  createdAt: string;
  updatedAt: string;
};

export type UserInput = {
  name: string;
  email: string;
  password?: string;
  role: string;
  isActive?: boolean;
  gender?: string;
  phone?: string;
  institution?: string;
  workStudyStatus?: WorkStudyStatus;
  country?: string;
  status?: UserStatus;
};

// ─── Members (mobile app users) ─────────────────────────────────────────────

export type MemberSubscriptionSummary = {
  id: number;
  packageId: number | null;
  packageName: string;
  packagePrice: string;
  subscribedAt: string;
  expiresAt: string;
  isActive: boolean;
  notes: string | null;
};

export type MemberDetail = {
  id: number;
  name: string;
  email: string;
  photoUrl: string | null;
  gender: string | null;
  phone: string | null;
  institution: string | null;
  workStudyStatus: WorkStudyStatus | null;
  country: string | null;
  status: UserStatus;
  createdAt: string;
  updatedAt: string;
  subscription: MemberSubscriptionSummary | null;
};

export type MemberInput = {
  name: string;
  email: string;
  password?: string;
  gender?: string;
  phone?: string;
  institution?: string;
  workStudyStatus?: WorkStudyStatus;
  country?: string;
  status?: UserStatus;
};

// ─── Subscription Packages ───────────────────────────────────────────────────

export type PackageInput = {
  name: string;
  description?: string;
  price: number;
  durationMonths: number;
  chatbotAccess?: boolean;
  accessibleCategoryIds?: number[];
  isActive?: boolean;
};

export type Package = {
  id: number;
  name: string;
  description: string | null;
  price: string;
  durationMonths: number;
  chatbotAccess: boolean;
  accessibleCategoryIds: number[] | null;
  isActive: boolean;
  subscriptionsCount?: number;
  createdAt: string;
  updatedAt: string;
};

// ─── Member Subscriptions ────────────────────────────────────────────────────

export type MemberSubscriptionInput = {
  packageId: number;
  subscribedAt?: string;
  expiresAt?: string;  // optional override
  notes?: string;
};

export type MemberSubscription = {
  id: number;
  userId: number;
  packageId: number | null;
  packageName: string;
  packagePrice: string;
  subscribedAt: string;
  expiresAt: string;
  isActive: boolean;
  notes: string | null;
  package?: Package;
};

// ─── Categories / Topics ─────────────────────────────────────────────────────

export type CategoryType = "notes" | "question_bank" | "case_law" | "statutes" | "game" | "quiz";

export type CategoryInput = {
  name: string;
  slug?: string;
  description?: string;
  type?: CategoryType;
  iconUrl?: string;
  sortOrder?: number;
  legalBasis?: string;
};

export type Category = {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  type: CategoryType;
  iconUrl: string | null;
  sortOrder: number;
  legalBasis: string | null;
  createdAt: string;
  updatedAt: string;
  _count?: { notes?: number; questions?: number; caseSummaries?: number; questionPapers?: number };
};

// ─── Topic Links (per-topic extra links) ─────────────────────────────────────

export type TopicLinkInput = {
  categoryId: number;
  title: string;
  url: string;
  sortOrder?: number;
  isActive?: boolean;
};

export type TopicLink = {
  id: number;
  categoryId: number;
  title: string;
  url: string;
  sortOrder: number;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
};

// ─── Notes ───────────────────────────────────────────────────────────────────

export type NoteInput = {
  title: string;
  description?: string;
  categoryId?: number;
  sortOrder?: number;
  isPublished?: boolean;
};

export type Note = {
  id: number;
  title: string;
  description: string | null;
  categoryId: number | null;
  filePath: string;
  fileName: string;
  fileType: string;
  fileSize: number;
  sortOrder: number;
  isPublished: boolean;
  category?: Category;
  createdAt: string;
  updatedAt: string;
};

// ─── Case Summaries ──────────────────────────────────────────────────────────

export type CaseSummaryInput = {
  title: string;
  citation: string;
  summaryText: string;
  categoryId?: number;
  isPublished?: boolean;
};

export type CaseSummary = {
  id: number;
  title: string;
  citation: string;
  summaryText: string;
  categoryId: number | null;
  pdfFilePath: string | null;
  isPublished: boolean;
  category?: Category;
  createdAt: string;
  updatedAt: string;
};

// ─── Questions (MCQ) ─────────────────────────────────────────────────────────

export type QuestionInput = {
  categoryId: number;
  questionText: string;
  options: string[];
  correctOptionIndex: number;
  explanation?: string;
  sortOrder?: number;
  isPublished?: boolean;
};

export type Question = {
  id: number;
  categoryId: number;
  questionText: string;
  options: string[];
  correctOptionIndex: number;
  explanation: string | null;
  sortOrder: number;
  isPublished: boolean;
  category?: Category;
  createdAt: string;
  updatedAt: string;
};

// ─── Question Papers (PDF-based) ─────────────────────────────────────────

export type QuestionPaperType = "past_year" | "topical";

export type QuestionPaperInput = {
  title: string;
  type: QuestionPaperType;
  year?: number;
  categoryId?: number;
  description?: string;
  sortOrder?: number;
  isPublished?: boolean;
};

export type QuestionPaper = {
  id: number;
  title: string;
  slug: string;
  type: QuestionPaperType;
  year: number | null;
  categoryId: number | null;
  description: string | null;
  filePath: string;
  fileName: string;
  fileType: string;
  fileSize: number;
  isPublished: boolean;
  sortOrder: number;
  category?: Category;
  createdAt: string;
  updatedAt: string;
};

// ─── Statutes ────────────────────────────────────────────────────────────

export type StatuteType = "link" | "document";

export type StatuteInput = {
  title: string;
  type: StatuteType;
  url?: string;
  description?: string;
  sortOrder?: number;
  isPublished?: boolean;
};

export type Statute = {
  id: number;
  title: string;
  slug: string;
  type: StatuteType;
  url: string | null;
  filePath: string | null;
  fileName: string | null;
  fileType: string | null;
  fileSize: number | null;
  description: string | null;
  isPublished: boolean;
  sortOrder: number;
  createdAt: string;
  updatedAt: string;
};

// ─── Free Links (Case Law) ──────────────────────────────────────────────────

export type FreeLinkInput = {
  title: string;
  url: string;
  iconImagePath?: string;
  sortOrder?: number;
  isActive?: boolean;
};

export type FreeLink = {
  id: number;
  title: string;
  url: string;
  iconImagePath: string | null;
  sortOrder: number;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
};

// ─── Media ───────────────────────────────────────────────────────────────────

export type Media = {
  id: number;
  filename: string;
  originalName: string;
  title: string | null;
  caption: string | null;
  description: string | null;
  mimeType: string;
  size: number;
  width: number | null;
  height: number | null;
  altText: string | null;
  path: string;
  url: string;
  createdAt: string;
};

export type MediaMetadataInput = {
  title: string;
  altText: string;
  caption: string;
  description: string;
};

// ─── Settings ────────────────────────────────────────────────────────────────

export type SettingsPayload = {
  siteTitle: string;
  tagline: string;
  siteIconUrl: string;
  sidebarLogoUrl: string;
  faviconUrl: string;
  language: string;
  timezone: string;
  footerText: string;
  companyName?: string;
  companyAddress?: string;
  brandColor?: string;
  aboutContent?: string;
  maintenanceMode?: string | boolean;
  supportEmail?: string;
  supportPhone?: string;
  websiteUrl?: string;
  facebookUrl?: string;
  instagramUrl?: string;
  twitterUrl?: string;
  linkedinUrl?: string;
  termsUrl?: string;
  privacyUrl?: string;
  appName?: string;
  upgradePromptMessage?: string;
  minimumAppVersion?: string;
  appStoreUrl?: string;
  googlePlayUrl?: string;
};

// ─── Roles ───────────────────────────────────────────────────────────────────

export type Role = {
  id: number;
  name: string;
  description: string;
  permissions: string[];
  createdAt: string;
  updatedAt: string;
};

export type RoleInput = {
  name: string;
  description: string;
  permissions: string[];
};

// ─── Audit Logs ──────────────────────────────────────────────────────────────

export type AuditLog = {
  id: number;
  userId: number | null;
  action: string;
  auditableType: string | null;
  auditableId: number | null;
  oldValues: Record<string, unknown> | null;
  newValues: Record<string, unknown> | null;
  ipAddress: string | null;
  userAgent: string | null;
  createdAt: string;
  user?: { id: number; name: string; email: string } | null;
};

// ─── Chatbot Settings ───────────────────────────────────────────────────────

export type ChatbotTestMessage = {
  id: string;
  role: "user" | "assistant";
  content: string;
  citations?: string[];
  responseTimeMs?: number;
  tokenCount?: number;
  timestamp: string;
};

// ─── Dashboard ───────────────────────────────────────────────────────────────

export type ChartItem = { label: string; value: number; color: string };
export type ContentHealthItem = { label: string; published: number; total: number; pct: number; color: string };

export type DashboardSummary = {
  counts: {
    admins: number;
    members: number;
    activeMembers: number;
    suspendedMembers: number;
    activeSubscriptions: number;
    notes: number;
    caseSummaries: number;
    questions: number;
    freeLinks: number;
    packages: number;
  };
  metrics: {
    conversionRate: number;
    activationRate: number;
    expiringSoon: number;
    popularPackage: string | null;
    popularPkgCount: number;
  };
  charts: {
    memberStatus: ChartItem[];
    subPipeline: ChartItem[];
  };
  recent: {
    members: Array<{ id: number; name: string; email: string; status: string; createdAt: string }>;
  };
};
