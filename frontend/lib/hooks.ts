"use client";

import { useEffect, useState } from "react";

/** Devuelve `value` con un retardo de `ms` ms desde el último cambio (para búsquedas). */
export function useDebounce<T>(value: T, ms = 300): T {
  const [debounced, setDebounced] = useState(value);

  useEffect(() => {
    const t = setTimeout(() => setDebounced(value), ms);
    return () => clearTimeout(t);
  }, [value, ms]);

  return debounced;
}
