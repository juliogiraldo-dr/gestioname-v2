import { defineConfig, devices } from "@playwright/test";

/**
 * Configuración E2E. Requiere el backend (tenant `demo`) y el frontend en marcha.
 * Antes de la primera ejecución:  npm i -D @playwright/test && npx playwright install chromium
 */
export default defineConfig({
  testDir: "./e2e",
  timeout: 30_000,
  use: {
    baseURL: process.env.E2E_BASE_URL ?? "http://localhost:3000",
    trace: "on-first-retry",
  },
  projects: [{ name: "chromium", use: { ...devices["Desktop Chrome"] } }],
});
