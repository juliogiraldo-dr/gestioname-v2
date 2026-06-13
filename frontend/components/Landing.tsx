"use client";

import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import Link from "next/link";
import { useBranding } from "@/lib/branding";
import { Badge } from "@/components/ui";
import {
  ClockIcon,
  UsersIcon,
  EuroIcon,
  BuildingIcon,
  EntityIcon,
  CheckIcon,
} from "@/components/icons";

const VALUE_PROPS: ReadonlyArray<{
  Icon: (p: { className?: string }) => React.ReactNode;
  title: string;
  desc: string;
}> = [
  {
    Icon: ClockIcon,
    title: "RRHH y fichajes",
    desc: "Control de jornada conforme al ET 34.9, ausencias, vacaciones y nóminas.",
  },
  {
    Icon: UsersIcon,
    title: "Socios y entidades",
    desc: "Gestión de asociaciones: altas de socios, cuotas y portal del socio.",
  },
  {
    Icon: EuroIcon,
    title: "Tesorería",
    desc: "Cobros, pagos y enlace contable listo para tu gestoría.",
  },
];

const STEPS: ReadonlyArray<{ n: string; title: string; desc: string }> = [
  { n: "1", title: "Regístrate", desc: "Crea tu cuenta gratis en menos de un minuto, sin tarjeta." },
  { n: "2", title: "Configura", desc: "Da de alta tu empresa o asociación y personaliza tu espacio." },
  { n: "3", title: "Gestiona", desc: "Empieza a fichar, gestionar socios y llevar la tesorería." },
];

type Plan = {
  slug: string;
  name: string;
  monthly: number;
  annual: number; // precio mensual facturando anual
  features: string[];
  highlight?: boolean;
};

// Valores REALES de respaldo (precio mensual en euros) si la API no responde.
const PLANS: ReadonlyArray<Plan> = [
  {
    slug: "free",
    name: "Free",
    monthly: 0,
    annual: 0,
    features: ["Hasta 5 empleados", "Fichaje y control de jornada", "1 empresa o entidad"],
  },
  {
    slug: "starter",
    name: "Starter",
    monthly: 9.9,
    annual: 9.9,
    features: ["Hasta 15 empleados", "Ausencias y vacaciones", "Portal del empleado", "Exportación a Excel"],
  },
  {
    slug: "professional",
    name: "Professional",
    monthly: 19.9,
    annual: 19.9,
    highlight: true,
    features: ["Hasta 50 empleados", "Socios y asociaciones", "Tesorería", "Informes avanzados", "Soporte prioritario"],
  },
  {
    slug: "business",
    name: "Business",
    monthly: 39.9,
    annual: 39.9,
    features: ["Empleados ilimitados", "Multi-empresa y multi-entidad", "Enlace contable a3asesor", "Marca blanca", "Acceso para tu gestoría"],
  },
];

type ApiPlan = {
  name: string;
  slug: string;
  price_monthly: number | string;
  price_yearly: number | string;
};

/** Precio mensual formateado en euros (p. ej. "9,90 €" o "0 €"). */
function formatEuro(value: number): string {
  return value % 1 === 0
    ? `${value} €`
    : `${value.toFixed(2).replace(".", ",")} €`;
}

const FAQ: ReadonlyArray<{ q: string; a: string }> = [
  {
    q: "¿Necesito tarjeta para empezar?",
    a: "No. El plan Free es gratuito y no requiere tarjeta de crédito. Puedes empezar a usarlo de inmediato.",
  },
  {
    q: "¿El control de jornada cumple la normativa?",
    a: "Sí. El registro de jornada es inmutable y conforme al artículo 34.9 del Estatuto de los Trabajadores; las correcciones quedan auditadas.",
  },
  {
    q: "¿Puedo gestionar una empresa y una asociación a la vez?",
    a: "Sí. Puedes elegir gestionar una empresa, una asociación o ambas dentro del mismo espacio.",
  },
  {
    q: "¿Dónde se alojan mis datos?",
    a: "Tus datos se alojan de forma aislada por organización y se cifran los campos sensibles (IBAN, DNI). Cumplimos con el RGPD/LOPD.",
  },
  {
    q: "¿Puedo exportar mi información?",
    a: "Sí. Puedes exportar tus datos en Excel y PDF, y generar el fichero de enlace contable para tu gestoría en cualquier momento.",
  },
  {
    q: "¿Puedo cambiar de plan más adelante?",
    a: "Por supuesto. Puedes subir o bajar de plan cuando quieras desde el panel de administración.",
  },
];

const USE_CASES: ReadonlyArray<{
  Icon: (p: { className?: string }) => React.ReactNode;
  title: string;
  desc: string;
}> = [
  {
    Icon: BuildingIcon,
    title: "Para empresas",
    desc: "Controla la jornada de tu equipo, gestiona ausencias y ten las nóminas a punto para tu gestoría.",
  },
  {
    Icon: EntityIcon,
    title: "Para asociaciones",
    desc: "Lleva el registro de socios, gestiona cuotas y ofrece un portal propio a tus miembros.",
  },
  {
    Icon: UsersIcon,
    title: "Para ambas",
    desc: "Si gestionas una empresa y una asociación, únelo todo en un único espacio sin duplicar trabajo.",
  },
];

export function Landing() {
  const { app_name } = useBranding();
  const [annual, setAnnual] = useState(true);
  // Empieza con los valores de respaldo y los sustituye por los reales de la API al montar.
  const [plans, setPlans] = useState<ReadonlyArray<Plan>>(PLANS);

  useEffect(() => {
    let active = true;
    void (async () => {
      try {
        const res = await api<{ data: ApiPlan[] }>("/plans", { auth: false });
        if (!active) return;
        const bySlug = new Map(res.data.map((p) => [p.slug, p]));
        setPlans(
          PLANS.map((plan) => {
            const real = bySlug.get(plan.slug);
            if (!real) return plan;
            const monthly = Number(real.price_monthly);
            const yearly = Number(real.price_yearly);
            return {
              ...plan,
              monthly: Number.isFinite(monthly) ? monthly : plan.monthly,
              annual: Number.isFinite(yearly) && yearly > 0 ? yearly / 12 : (Number.isFinite(monthly) ? monthly : plan.annual),
            };
          }),
        );
      } catch {
        // Se mantienen los valores de respaldo ya cargados.
      }
    })();
    return () => {
      active = false;
    };
  }, []);

  return (
    <main className="min-h-full">
      <header className="sticky top-0 z-30 border-b border-line bg-canvas/80 backdrop-blur">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4 sm:px-10">
          <span className="text-lg font-semibold text-primary">{app_name}</span>
          <div className="flex items-center gap-4">
            <Link href="/login" className="text-sm font-medium text-ink-soft hover:text-primary">
              Iniciar sesión
            </Link>
            <Link
              href="/onboarding"
              className="inline-flex items-center justify-center rounded-[var(--radius-fluent)] bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-600"
            >
              Empieza gratis
            </Link>
          </div>
        </div>
      </header>

      {/* Hero */}
      <section className="mx-auto max-w-6xl px-6 py-16 text-center sm:px-10 lg:py-24">
        <h1 className="mx-auto max-w-3xl text-4xl font-semibold leading-tight text-primary sm:text-5xl">
          La gestión de tu empresa o asociación, sin complicaciones
        </h1>
        <p className="mx-auto mt-5 max-w-xl text-lg text-ink-soft">
          RRHH, control de jornada, socios y tesorería en una sola plataforma. Empieza gratis, sin tarjeta y en menos de
          un minuto.
        </p>
        <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
          <Link
            href="/onboarding"
            className="inline-flex w-full items-center justify-center rounded-[var(--radius-fluent)] bg-primary px-6 py-3 text-sm font-medium text-white transition-colors hover:bg-primary-600 sm:w-auto"
          >
            Empieza gratis
          </Link>
          <Link
            href="/login"
            className="inline-flex w-full items-center justify-center rounded-[var(--radius-fluent)] bg-secondary/15 px-6 py-3 text-sm font-medium text-primary transition-colors hover:bg-secondary/25 sm:w-auto"
          >
            Ver demo
          </Link>
        </div>
      </section>

      {/* Propuesta de valor */}
      <section className="mx-auto max-w-6xl px-6 py-12 sm:px-10">
        <div className="grid gap-6 sm:grid-cols-3">
          {VALUE_PROPS.map(({ Icon, title, desc }) => (
            <div key={title} className="rounded-[var(--radius-fluent)] border border-line bg-surface p-6 shadow-[var(--shadow-fluent)]">
              <span className="flex h-11 w-11 items-center justify-center rounded-[var(--radius-fluent)] bg-secondary/20 text-primary">
                <Icon className="h-6 w-6" />
              </span>
              <h3 className="mt-4 text-lg font-semibold text-ink">{title}</h3>
              <p className="mt-1.5 text-sm text-ink-soft">{desc}</p>
            </div>
          ))}
        </div>
      </section>

      {/* Cómo funciona */}
      <section className="mx-auto max-w-6xl px-6 py-12 sm:px-10">
        <h2 className="text-center text-2xl font-semibold text-primary">Cómo funciona</h2>
        <div className="mt-8 grid gap-6 sm:grid-cols-3">
          {STEPS.map(({ n, title, desc }) => (
            <div key={n} className="text-center">
              <span className="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-primary text-sm font-semibold text-white">
                {n}
              </span>
              <h3 className="mt-4 text-lg font-semibold text-ink">{title}</h3>
              <p className="mt-1.5 text-sm text-ink-soft">{desc}</p>
            </div>
          ))}
        </div>
      </section>

      {/* Planes y precios */}
      <section id="precios" className="mx-auto max-w-6xl px-6 py-12 sm:px-10">
        <h2 className="text-center text-2xl font-semibold text-primary">Planes y precios</h2>
        <p className="mt-2 text-center text-sm text-ink-soft">Empieza gratis y crece cuando lo necesites.</p>

        <div className="mt-6 flex items-center justify-center gap-3 text-sm">
          <span className={annual ? "text-ink-soft" : "font-medium text-primary"}>Mensual</span>
          <button
            type="button"
            onClick={() => setAnnual((a) => !a)}
            aria-label="Cambiar facturación"
            className="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full bg-secondary/40 transition-colors"
          >
            <span
              className={`inline-block h-5 w-5 transform rounded-full bg-primary shadow transition-transform ${
                annual ? "translate-x-5" : "translate-x-0.5"
              }`}
            />
          </button>
          <span className={annual ? "font-medium text-primary" : "text-ink-soft"}>Anual</span>
          <Badge tone="ok">2 meses gratis</Badge>
        </div>

        <div className="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
          {plans.map((plan) => {
            const price = annual ? plan.annual : plan.monthly;
            return (
              <div
                key={plan.name}
                className={`relative flex flex-col rounded-[var(--radius-fluent)] border bg-surface p-6 shadow-[var(--shadow-fluent)] ${
                  plan.highlight ? "border-secondary ring-2 ring-secondary/30" : "border-line"
                }`}
              >
                {plan.highlight && (
                  <span className="absolute -top-3 left-1/2 -translate-x-1/2">
                    <Badge tone="info">Recomendado</Badge>
                  </span>
                )}
                <h3 className="text-lg font-semibold text-primary">{plan.name}</h3>
                <p className="mt-3">
                  <span className="text-3xl font-semibold text-ink">{formatEuro(price)}</span>
                  <span className="text-sm text-ink-soft">/mes</span>
                </p>
                {annual && plan.monthly > 0 && (
                  <p className="mt-1 text-xs text-ink-soft">Facturación anual</p>
                )}
                <ul className="mt-5 flex-1 space-y-2 text-sm text-ink-soft">
                  {plan.features.map((f) => (
                    <li key={f} className="flex items-start gap-2">
                      <CheckIcon className="mt-0.5 h-4 w-4 shrink-0 text-accent" />
                      <span>{f}</span>
                    </li>
                  ))}
                </ul>
                <Link
                  href="/onboarding"
                  className={`mt-6 inline-flex items-center justify-center rounded-[var(--radius-fluent)] px-4 py-2 text-sm font-medium transition-colors ${
                    plan.highlight
                      ? "bg-primary text-white hover:bg-primary-600"
                      : "bg-secondary/15 text-primary hover:bg-secondary/25"
                  }`}
                >
                  Empezar
                </Link>
              </div>
            );
          })}
        </div>
      </section>

      {/* Casos de uso */}
      <section className="mx-auto max-w-6xl px-6 py-12 sm:px-10">
        <h2 className="text-center text-2xl font-semibold text-primary">Para quién es</h2>
        <div className="mt-8 grid gap-6 sm:grid-cols-3">
          {USE_CASES.map(({ Icon, title, desc }) => (
            <div key={title} className="rounded-[var(--radius-fluent)] border border-line bg-surface p-6 shadow-[var(--shadow-fluent)]">
              <span className="flex h-11 w-11 items-center justify-center rounded-[var(--radius-fluent)] bg-accent/20 text-primary">
                <Icon className="h-6 w-6" />
              </span>
              <h3 className="mt-4 text-lg font-semibold text-ink">{title}</h3>
              <p className="mt-1.5 text-sm text-ink-soft">{desc}</p>
            </div>
          ))}
        </div>
      </section>

      {/* FAQ */}
      <section className="mx-auto max-w-3xl px-6 py-12 sm:px-10">
        <h2 className="text-center text-2xl font-semibold text-primary">Preguntas frecuentes</h2>
        <div className="mt-8 space-y-3">
          {FAQ.map(({ q, a }) => (
            <details
              key={q}
              className="group rounded-[var(--radius-fluent)] border border-line bg-surface p-4 shadow-[var(--shadow-fluent)]"
            >
              <summary className="flex cursor-pointer items-center justify-between gap-3 text-sm font-medium text-ink marker:content-none">
                {q}
                <span className="text-ink-soft transition-transform group-open:rotate-45">+</span>
              </summary>
              <p className="mt-3 text-sm text-ink-soft">{a}</p>
            </details>
          ))}
        </div>
      </section>

      {/* CTA final */}
      <section className="mx-auto max-w-6xl px-6 py-12 sm:px-10">
        <div className="rounded-[var(--radius-fluent)] bg-primary px-6 py-12 text-center text-white sm:px-10">
          <h2 className="text-2xl font-semibold">Empieza hoy, gratis</h2>
          <p className="mx-auto mt-2 max-w-md text-sm text-white/80">
            Crea tu espacio en menos de un minuto. Sin tarjeta, sin compromiso.
          </p>
          <Link
            href="/onboarding"
            className="mt-6 inline-flex items-center justify-center rounded-[var(--radius-fluent)] bg-white px-6 py-3 text-sm font-medium text-primary transition-colors hover:bg-white/90"
          >
            Empieza gratis
          </Link>
        </div>
      </section>

      {/* Footer */}
      <footer className="border-t border-line">
        <div className="mx-auto grid max-w-6xl gap-8 px-6 py-12 sm:px-10 md:grid-cols-4">
          <div>
            <span className="text-lg font-semibold text-primary">{app_name}</span>
            <p className="mt-2 text-sm text-ink-soft">
              Gestión de RRHH, socios y tesorería para empresas y asociaciones.
            </p>
          </div>
          <div>
            <h3 className="text-sm font-semibold text-ink">Producto</h3>
            <ul className="mt-3 space-y-2 text-sm text-ink-soft">
              <li><a href="#precios" className="hover:text-primary">Precios</a></li>
              <li><Link href="/onboarding" className="hover:text-primary">Empieza gratis</Link></li>
              <li><Link href="/login" className="hover:text-primary">Iniciar sesión</Link></li>
            </ul>
          </div>
          <div>
            <h3 className="text-sm font-semibold text-ink">Legal</h3>
            <ul className="mt-3 space-y-2 text-sm text-ink-soft">
              <li><Link href="/legal/privacidad" className="hover:text-primary">Privacidad</Link></li>
              <li><Link href="/legal/terminos" className="hover:text-primary">Términos</Link></li>
              <li><Link href="/legal/cookies" className="hover:text-primary">Cookies</Link></li>
              <li><Link href="/legal/dpa" className="hover:text-primary">DPA</Link></li>
            </ul>
          </div>
          <div>
            <h3 className="text-sm font-semibold text-ink">Contacto</h3>
            <ul className="mt-3 space-y-2 text-sm text-ink-soft">
              <li>
                <a href="mailto:info@datarecover.es" className="hover:text-primary">info@datarecover.es</a>
              </li>
              <li>Datarecover S.L.</li>
              <li>Majadahonda, Madrid</li>
            </ul>
          </div>
        </div>
        <div className="border-t border-line py-6 text-center text-xs text-ink-soft">
          © {new Date().getFullYear()} Datarecover S.L. Todos los derechos reservados.
        </div>
      </footer>
    </main>
  );
}
