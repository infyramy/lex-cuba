import { ref } from "vue";

export type ToastVariant = "success" | "error" | "info";

export type ToastItem = {
  id: number;
  title: string;
  message?: string;
  variant: ToastVariant;
  durationMs: number;
};

const MAX_VISIBLE = 3;
const toasts = ref<ToastItem[]>([]);
let nextToastId = 1;

function remove(id: number) {
  toasts.value = toasts.value.filter((item) => item.id !== id);
}

function push(input: {
  title: string;
  message?: string;
  variant?: ToastVariant;
  durationMs?: number;
}) {
  const toast: ToastItem = {
    id: nextToastId++,
    title: input.title,
    message: input.message,
    variant: input.variant ?? "info",
    durationMs: input.durationMs ?? 4000,
  };

  toasts.value = [...toasts.value, toast];

  // Cap visible toasts — remove oldest when exceeding limit
  while (toasts.value.length > MAX_VISIBLE) {
    toasts.value.shift();
  }

  window.setTimeout(() => remove(toast.id), toast.durationMs);
  return toast.id;
}

export function useToast() {
  return {
    toasts,
    remove,
    push,
    success: (title: string, message?: string, durationMs?: number) =>
      push({ title, message, durationMs, variant: "success" }),
    error: (title: string, message?: string, durationMs?: number) =>
      push({ title, message, durationMs, variant: "error" }),
    info: (title: string, message?: string, durationMs?: number) =>
      push({ title, message, durationMs, variant: "info" }),
  };
}
