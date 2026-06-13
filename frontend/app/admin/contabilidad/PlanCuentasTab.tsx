"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import { api, ApiError } from "@/lib/api";
import { useToast } from "@/lib/toast";
import { useConfirm } from "@/lib/confirm";
import {
  Badge,
  Button,
  Card,
  EmptyState,
  Modal,
  SelectField,
  Skeleton,
  TextField,
  Toggle,
} from "@/components/ui";
import { ACCOUNT_TYPES, accountTypeLabel, type Account, type AccountType } from "./shared";

export default function PlanCuentasTab() {
  const toast = useToast();
  const confirm = useConfirm();
  const [accounts, setAccounts] = useState<Account[] | null>(null);
  const [search, setSearch] = useState("");
  const [reloadKey, setReloadKey] = useState(0);
  const [editing, setEditing] = useState<Account | "new" | null>(null);

  const reload = useCallback(() => setReloadKey((k) => k + 1), []);

  useEffect(() => {
    let active = true;
    void (async () => {
      try {
        const res = await api<{ data: Account[] }>("/accounting/accounts");
        if (active) setAccounts(res.data);
      } catch {
        if (active) setAccounts([]);
      }
    })();
    return () => {
      active = false;
    };
  }, [reloadKey]);

  const grouped = useMemo(() => {
    const list = (accounts ?? [])
      .filter((a) => {
        const q = search.trim().toLowerCase();
        if (!q) return true;
        return a.code.toLowerCase().includes(q) || a.name.toLowerCase().includes(q);
      })
      .slice()
      .sort((a, b) => a.code.localeCompare(b.code, "es", { numeric: true }));
    return ACCOUNT_TYPES.map(([type]) => ({
      type,
      accounts: list.filter((a) => a.type === type),
    })).filter((g) => g.accounts.length > 0);
  }, [accounts, search]);

  async function remove(account: Account) {
    const ok = await confirm({
      title: `Eliminar cuenta ${account.code}`,
      message: `¿Eliminar «${account.name}»? Esta acción no se puede deshacer.`,
    });
    if (!ok) return;
    try {
      await api(`/accounting/accounts/${account.id}`, { method: "DELETE" });
      toast.success("Cuenta eliminada.");
      reload();
    } catch (e) {
      if (e instanceof ApiError && e.code === "ACCOUNT_HAS_MOVEMENTS") {
        toast.error("No se puede eliminar: la cuenta tiene movimientos contables.");
      } else {
        toast.error(e instanceof ApiError ? e.message : "No se pudo eliminar la cuenta.");
      }
    }
  }

  if (accounts === null) return <Skeleton rows={6} />;

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-end justify-between gap-3">
        <div className="max-w-sm flex-1">
          <TextField label="Buscar cuenta" value={search} onChange={setSearch} placeholder="Código o nombre" />
        </div>
        <Button onClick={() => setEditing("new")}>Nueva cuenta</Button>
      </div>

      {grouped.length === 0 ? (
        <EmptyState
          title="Sin cuentas"
          message={search.trim() ? "Ninguna cuenta coincide con la búsqueda." : "Aún no hay cuentas en el plan contable."}
        />
      ) : (
        <div className="space-y-5">
          {grouped.map((group) => (
            <div key={group.type}>
              <h3 className="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-soft">
                {accountTypeLabel(group.type)}
              </h3>
              <Card className="divide-y divide-line">
                {group.accounts.map((a) => (
                  <div key={a.id} className="flex items-center justify-between gap-4 p-3">
                    <div className="flex items-center gap-3">
                      <span className="font-mono text-sm text-primary">{a.code}</span>
                      <span className="text-sm text-ink">{a.name}</span>
                      {!a.active && <Badge tone="neutral">Inactiva</Badge>}
                    </div>
                    <div className="flex items-center gap-1">
                      <Button variant="ghost" onClick={() => setEditing(a)}>Editar</Button>
                      <Button variant="ghost" onClick={() => void remove(a)}>Eliminar</Button>
                    </div>
                  </div>
                ))}
              </Card>
            </div>
          ))}
        </div>
      )}

      {editing && (
        <AccountModal
          account={editing === "new" ? null : editing}
          onClose={() => setEditing(null)}
          onDone={() => {
            setEditing(null);
            reload();
          }}
        />
      )}
    </div>
  );
}

function AccountModal({
  account,
  onClose,
  onDone,
}: {
  account: Account | null;
  onClose: () => void;
  onDone: () => void;
}) {
  const toast = useToast();
  const [code, setCode] = useState(account?.code ?? "");
  const [name, setName] = useState(account?.name ?? "");
  const [type, setType] = useState<AccountType>(account?.type ?? "activo");
  const [active, setActive] = useState(account?.active ?? true);
  const [saving, setSaving] = useState(false);

  async function submit() {
    if (!code.trim() || !name.trim()) {
      toast.warning("Indica el código y el nombre de la cuenta.");
      return;
    }
    setSaving(true);
    try {
      const body = { code: code.trim(), name: name.trim(), type, active };
      if (account) {
        await api(`/accounting/accounts/${account.id}`, { method: "PUT", body });
        toast.success("Cuenta actualizada.");
      } else {
        await api("/accounting/accounts", { method: "POST", body });
        toast.success("Cuenta creada.");
      }
      onDone();
    } catch (e) {
      toast.error(e instanceof ApiError ? e.message : "No se pudo guardar la cuenta.");
    } finally {
      setSaving(false);
    }
  }

  return (
    <Modal title={account ? `Editar cuenta ${account.code}` : "Nueva cuenta"} onClose={onClose}>
      <div className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-2">
          <TextField label="Código" value={code} onChange={setCode} placeholder="Ej. 5720" />
          <SelectField label="Tipo" value={type} onChange={(v) => setType(v as AccountType)} options={ACCOUNT_TYPES} />
        </div>
        <TextField label="Nombre" value={name} onChange={setName} placeholder="Ej. Banco c/c" />
        <Toggle on={active} onClick={() => setActive((v) => !v)} label="Cuenta activa" />
        <div className="flex justify-end gap-2">
          <Button variant="ghost" onClick={onClose}>Cancelar</Button>
          <Button onClick={submit} disabled={saving}>{saving ? "Guardando…" : "Guardar"}</Button>
        </div>
      </div>
    </Modal>
  );
}
