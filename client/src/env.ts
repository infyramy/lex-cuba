// Use ?? so an explicitly empty VITE_API_BASE_URL="" (Vite proxy mode) is respected
export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? "http://127.0.0.1:8000";
