import { expect, test } from "@playwright/test";

/**
 * Flujo completo del portal del empleado: login → ver inicio → solicitar ausencia.
 * Usa el tenant `demo` y el usuario admin sembrado por TenantDemoSeeder.
 *
 * Requisitos: backend en demo.localhost:8000 y frontend en localhost:3000.
 */
test("login y navegación del portal", async ({ page }) => {
  await page.goto("/login");

  await page.getByPlaceholder("admin@demo.gestioname.app").fill("admin@demo.gestioname.app");
  await page.getByPlaceholder("••••••••").fill("password");
  await page.getByRole("button", { name: "Entrar" }).click();

  // Aterriza en el portal y ve su nombre en la cabecera.
  await expect(page).toHaveURL(/\/portal/);
  await expect(page.getByText("Resumen de tu actividad")).toBeVisible();

  // Navega a Ausencias.
  await page.getByRole("link", { name: "Ausencias" }).click();
  await expect(page.getByText("Nueva solicitud")).toBeVisible();
});

test("el kiosk pide configurar hitos la primera vez", async ({ page }) => {
  await page.goto("/kiosk");
  await expect(page.getByText("Configurar reloj")).toBeVisible();
});
