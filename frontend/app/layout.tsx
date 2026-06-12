import type { Metadata } from "next";
import "./globals.css";
import { AuthProvider } from "@/lib/auth";
import { BrandingProvider } from "@/lib/branding";
import { ToastProvider } from "@/lib/toast";
import { ConfirmProvider } from "@/lib/confirm";
import { QueryProvider } from "@/lib/query";
import { GlobalApiEvents } from "@/components/GlobalApiEvents";

export const metadata: Metadata = {
  title: "Gestioname",
  description: "Plataforma de gestión de RRHH y control de jornada",
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="es" className="h-full">
      <body className="min-h-full">
        <QueryProvider>
          <ToastProvider>
            <ConfirmProvider>
              <BrandingProvider>
                <AuthProvider>
                  <GlobalApiEvents />
                  {children}
                </AuthProvider>
              </BrandingProvider>
            </ConfirmProvider>
          </ToastProvider>
        </QueryProvider>
      </body>
    </html>
  );
}
