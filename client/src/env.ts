// Local dev: VITE_API_BASE_URL="" → empty string → Vite proxy (same-origin, no CORS)
// Production: VITE_API_BASE_URL="https://lex-api.0w0.my" → baked in at Docker build
export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? "https://lex-api.0w0.my";
