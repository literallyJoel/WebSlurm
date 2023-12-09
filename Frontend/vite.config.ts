import { defineConfig } from "vite";
import path from "path";
import react from "@vitejs/plugin-react-swc";

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
  //Routes any calls to the /api route to 8080 during development
  server: {
    proxy: {
      "/api": "http://localhost:8080",
    },
  },
});
