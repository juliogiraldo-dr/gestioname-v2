import Link from "next/link";
import type { ReactNode } from "react";

/** Carcasa compartida para las páginas legales: columna legible y centrada. */
export default function LegalLayout({ children }: { children: ReactNode }) {
  return (
    <main className="min-h-full">
      <div className="mx-auto max-w-3xl px-6 py-10 sm:py-16">
        <Link href="/" className="text-sm font-medium text-primary hover:underline">
          ← Volver
        </Link>
        <article className="prose-legal mt-6">{children}</article>
        <footer className="mt-12 border-t border-line pt-6 text-xs text-ink-soft">
          Datarecover S.L. · Majadahonda, Madrid · info@datarecover.es
        </footer>
      </div>
    </main>
  );
}
