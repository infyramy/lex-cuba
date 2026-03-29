import type { Component } from "vue";
import {
  BookOpen,
  Bot,
  CreditCard,
  FileText,
  FolderOpen,
  HelpCircle,
  LayoutDashboard,
  Link,
  Library,
  Scale,
  Settings,
  Users,
  UserCheck,
  Wallet,
} from "lucide-vue-next";

export type MenuNode = {
  id: string;
  label: string;
  to: string;
  children?: MenuNode[];
};

export type MenuItemDef = MenuNode & {
  icon: Component;
};

export type MenuGroupDef = {
  id: string;
  label: string;
  items: MenuItemDef[];
};

export type AdminMenuPrefs = {
  groupOrder: string[];
  itemOrder: Record<string, string[]>;
  childOrder: Record<string, string[]>;
  grandchildOrder: Record<string, string[]>;
  hidden: string[];
  hiddenChildren: string[];
  hiddenGrandchildren: string[];
  hiddenGroups: string[];
};

export const DEFAULT_MENU: MenuGroupDef[] = [
  // ── Dashboard ──────────────────────────────────────────────────
  {
    id: "dashboard",
    label: "",
    items: [
      { id: "dashboard", label: "Dashboard", to: "/admin", icon: LayoutDashboard },
    ],
  },

  // ── Members ────────────────────────────────────────────────────
  {
    id: "members",
    label: "Members",
    items: [
      { id: "members", label: "Members", to: "/admin/members", icon: UserCheck },
      { id: "packages", label: "Plans", to: "/admin/packages", icon: CreditCard },
      { id: "payments", label: "**Payments", to: "/admin/payments", icon: Wallet },
    ],
  },

  // ── Content ────────────────────────────────────────────────────
  {
    id: "content",
    label: "Content",
    items: [
      { id: "topics",         label: "Topics",         to: "/admin/topics",          icon: FolderOpen },
      { id: "notes",          label: "Notes",          to: "/admin/notes",           icon: FileText },
      { id: "case-summaries", label: "Case Summaries", to: "/admin/case-summaries",  icon: Scale },
      { id: "questions",      label: "Question Bank",  to: "/admin/questions",       icon: HelpCircle },
      { id: "statutes",       label: "Statutes",       to: "/admin/statutes",        icon: Library },
      { id: "free-links",     label: "Case Law",       to: "/admin/free-links",      icon: Link },
      { id: "chatbot",        label: "**Chatbot",      to: "/admin/chatbot",         icon: Bot },
    ],
  },

  // ── System ─────────────────────────────────────────────────────
  {
    id: "system",
    label: "System",
    items: [
      { id: "admin-users", label: "Admin Users",      to: "/admin/users",        icon: Users },
      { id: "settings",    label: "Settings",          to: "/admin/settings",     icon: Settings },
      { id: "api-docs",    label: "API Reference",     to: "/admin/api-reference", icon: BookOpen },
    ],
  },
];
