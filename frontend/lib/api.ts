// Cliente HTTP de la API de Gestioname.
// El tenant se resuelve por subdominio del host de la API (p. ej. demo.gestioname.app),
// por lo que NEXT_PUBLIC_API_URL debe apuntar al host del tenant.

const BASE = process.env.NEXT_PUBLIC_API_URL ?? "http://demo.localhost:8000/api/v1";

// En desarrollo local no hay subdominio real (localhost:3000 → API en localhost:8000).
// NEXT_PUBLIC_TENANT fija el tenant vía la cabecera X-Tenant-ID, que el backend acepta
// como alternativa al subdominio. En producción se sirve por subdominio y se deja vacío.
const TENANT = process.env.NEXT_PUBLIC_TENANT;

const TOKEN_KEY = "gm_token";

/** Cabecera de tenant para desarrollo local, o vacío si se resuelve por subdominio. */
function tenantHeaders(): Record<string, string> {
  return TENANT ? { "X-Tenant-ID": TENANT } : {};
}

export function getToken(): string | null {
  if (typeof window === "undefined") return null;
  return window.localStorage.getItem(TOKEN_KEY);
}

export function setToken(token: string | null): void {
  if (typeof window === "undefined") return;
  if (token) {
    window.localStorage.setItem(TOKEN_KEY, token);
  } else {
    window.localStorage.removeItem(TOKEN_KEY);
  }
}

export class ApiError extends Error {
  constructor(
    public readonly status: number,
    message: string,
    public readonly code?: string,
    public readonly errors?: Record<string, string[]>,
  ) {
    super(message);
  }
}

/** Timeout por defecto de las peticiones (ms). Evita que una llamada cuelgue la UI. */
export const REQUEST_TIMEOUT_MS = 15000;

/** fetch con AbortController: lanza ApiError(0, "TIMEOUT") si supera el timeout, o "NETWORK" si falla la red. */
async function timedFetch(url: string, init: RequestInit, timeoutMs = REQUEST_TIMEOUT_MS): Promise<Response> {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);
  try {
    return await fetch(url, { ...init, signal: controller.signal });
  } catch (e) {
    if (e instanceof DOMException && e.name === "AbortError") {
      throw new ApiError(0, "La solicitud ha tardado demasiado. Inténtalo de nuevo.", "TIMEOUT");
    }
    throw new ApiError(0, "No se pudo conectar con el servidor.", "NETWORK");
  } finally {
    clearTimeout(timer);
  }
}

type ApiOptions = {
  method?: string;
  body?: unknown;
  auth?: boolean;
  /** Fuerza el tenant (cabecera X-Tenant-ID) para esta llamada, en vez del de entorno. */
  tenant?: string;
  /** Timeout en ms (por defecto REQUEST_TIMEOUT_MS). */
  timeoutMs?: number;
};

export async function api<T = unknown>(path: string, options: ApiOptions = {}): Promise<T> {
  const { method = "GET", body, auth = true, tenant, timeoutMs } = options;

  const headers: Record<string, string> = {
    Accept: "application/json",
    ...(tenant ? { "X-Tenant-ID": tenant } : tenantHeaders()),
  };
  if (body !== undefined) headers["Content-Type"] = "application/json";

  if (auth) {
    const token = getToken();
    if (token) headers.Authorization = `Bearer ${token}`;
  }

  const res = await timedFetch(
    `${BASE}${path}`,
    { method, headers, body: body !== undefined ? JSON.stringify(body) : undefined },
    timeoutMs,
  );

  if (res.status === 204) return undefined as T;

  const data = await res.json().catch(() => ({}));

  if (!res.ok) {
    // Eventos globales: sesión expirada (401) y límite de plan (402).
    if (typeof window !== "undefined") {
      if (res.status === 401 && auth) window.dispatchEvent(new CustomEvent("gm:unauthorized"));
      if (res.status === 402) window.dispatchEvent(new CustomEvent("gm:plan-limit", { detail: data }));
    }
    throw new ApiError(
      res.status,
      (data as { message?: string }).message ?? "Error de red",
      (data as { code?: string }).code,
      (data as { errors?: Record<string, string[]> }).errors,
    );
  }

  return data as T;
}

/**
 * Sube un fichero (multipart/form-data) con campos adicionales opcionales.
 */
export async function uploadFile<T = unknown>(
  path: string,
  file: File,
  fields: Record<string, string> = {},
): Promise<T> {
  const form = new FormData();
  form.append("file", file);
  for (const [k, v] of Object.entries(fields)) form.append(k, v);

  const headers: Record<string, string> = { Accept: "application/json", ...tenantHeaders() };
  const token = getToken();
  if (token) headers.Authorization = `Bearer ${token}`;
  // No fijar Content-Type: el navegador añade el boundary del multipart.

  const res = await timedFetch(`${BASE}${path}`, { method: "POST", headers, body: form }, 60000);
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new ApiError(
      res.status,
      (data as { message?: string }).message ?? "No se pudo subir el fichero",
      (data as { code?: string }).code,
      (data as { errors?: Record<string, string[]> }).errors,
    );
  }
  return data as T;
}

/**
 * Obtiene un recurso binario autenticado (p. ej. el avatar) como object URL para
 * mostrarlo en un <img>. Devuelve null si no existe (404) o falla. El llamante debe
 * liberar la URL con URL.revokeObjectURL cuando deje de usarla.
 */
export async function fetchBlobUrl(path: string): Promise<string | null> {
  const headers: Record<string, string> = { Accept: "*/*", ...tenantHeaders() };
  const token = getToken();
  if (token) headers.Authorization = `Bearer ${token}`;

  try {
    const res = await timedFetch(`${BASE}${path}`, { method: "GET", headers });
    if (!res.ok) return null;
    return window.URL.createObjectURL(await res.blob());
  } catch {
    return null;
  }
}

/**
 * Descarga un fichero binario (Excel/PDF/ZIP) de un endpoint que responde con
 * `Content-Disposition: attachment`. Lanza un guardado en el navegador.
 */
export async function downloadFile(
  path: string,
  options: { method?: string; body?: unknown; fallbackName?: string } = {},
): Promise<void> {
  const { method = "POST", body, fallbackName = "descarga" } = options;

  const headers: Record<string, string> = { Accept: "*/*", ...tenantHeaders() };
  if (body !== undefined) headers["Content-Type"] = "application/json";
  const token = getToken();
  if (token) headers.Authorization = `Bearer ${token}`;

  const res = await timedFetch(
    `${BASE}${path}`,
    { method, headers, body: body !== undefined ? JSON.stringify(body) : undefined },
    60000,
  );

  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new ApiError(
      res.status,
      (data as { message?: string }).message ?? "No se pudo generar el fichero",
      (data as { code?: string }).code,
      (data as { errors?: Record<string, string[]> }).errors,
    );
  }

  const blob = await res.blob();
  const disposition = res.headers.get("Content-Disposition") ?? "";
  const match = /filename="?([^"]+)"?/.exec(disposition);
  const filename = match?.[1] ?? fallbackName;

  const url = window.URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  a.remove();
  window.URL.revokeObjectURL(url);
}
