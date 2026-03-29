import { defineConfig, loadEnv } from "vite";
import vue from "@vitejs/plugin-vue";
import path from "node:path";

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, __dirname, "");
  const backendUrl = env.VITE_BACKEND_URL || "http://127.0.0.1:8000";

  return {
    plugins: [vue()],
    resolve: {
      alias: {
        "@": path.resolve(__dirname, "src"),
      },
    },
    server: {
      host: "0.0.0.0",
      port: 5180,
      proxy: {
        "/api": {
          target: backendUrl,
          changeOrigin: true,
        },
        "/sanctum": {
          target: backendUrl,
          changeOrigin: true,
        },
        "/storage": {
          target: backendUrl,
          changeOrigin: true,
        },
      },
    },
  };
});
