"use client";

import { useEffect, useRef, useState } from "react";
import Link from "next/link";
import { api, uploadFile, fetchBlobUrl, ApiError } from "@/lib/api";
import { useToast } from "@/lib/toast";
import { Avatar, Button, Card, EmptyState, FormSection, PageHeader, Skeleton, TextField } from "@/components/ui";

type MeEmployee = {
  full_name: string;
  first_name?: string;
  last_name?: string;
  phone_personal?: string | null;
  address?: string | null;
  postal_code?: string | null;
  city?: string | null;
  province?: string | null;
  has_avatar?: boolean;
};

export default function MisDatosPage() {
  const toast = useToast();
  const [emp, setEmp] = useState<MeEmployee | null>(null);
  const [form, setForm] = useState({
    first_name: "", last_name: "", phone_personal: "", address: "", postal_code: "", city: "", province: "",
  });
  const [avatarUrl, setAvatarUrl] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [loaded, setLoaded] = useState(false);
  const fileInput = useRef<HTMLInputElement>(null);

  useEffect(() => {
    let url: string | null = null;
    void (async () => {
      try {
        const res = await api<{ data: { employee: MeEmployee | null } }>("/me");
        const e = res.data.employee;
        setEmp(e);
        if (e) {
          setForm({
            first_name: e.first_name ?? "", last_name: e.last_name ?? "",
            phone_personal: e.phone_personal ?? "", address: e.address ?? "",
            postal_code: e.postal_code ?? "", city: e.city ?? "", province: e.province ?? "",
          });
          if (e.has_avatar) {
            url = await fetchBlobUrl("/me/avatar");
            setAvatarUrl(url);
          }
        }
      } catch {
        setEmp(null);
      } finally {
        setLoaded(true);
      }
    })();
    return () => {
      if (url) window.URL.revokeObjectURL(url);
    };
  }, []);

  async function save() {
    setSaving(true);
    try {
      await api("/me/profile", { method: "PUT", body: form });
      toast.success("Datos guardados.");
    } catch (e) {
      toast.error(e instanceof ApiError ? e.message : "No se pudieron guardar los datos.");
    } finally {
      setSaving(false);
    }
  }

  async function onAvatar(file: File) {
    try {
      await uploadFile("/me/avatar", file);
      const url = await fetchBlobUrl("/me/avatar");
      setAvatarUrl((prev) => {
        if (prev) window.URL.revokeObjectURL(prev);
        return url;
      });
      toast.success("Foto actualizada.");
    } catch (e) {
      toast.error(e instanceof ApiError ? e.message : "No se pudo subir la foto.");
    }
  }

  if (!loaded) {
    return (
      <div>
        <PageHeader title="Mis datos" subtitle="Tus datos de contacto" />
        <Skeleton rows={5} />
      </div>
    );
  }

  if (emp === null) {
    return (
      <div>
        <PageHeader title="Mis datos" subtitle="Tus datos de contacto" />
        <EmptyState
          title="Sin ficha de empleado"
          message="No tienes ningún empleado vinculado a esta cuenta."
          action={<Link href="/portal"><Button variant="secondary">Ir al inicio</Button></Link>}
        />
      </div>
    );
  }

  return (
    <div>
      <PageHeader title="Mis datos" subtitle="Actualiza tu información de contacto y tu foto" />

      <Card className="mb-6 flex items-center gap-4 p-5">
        {avatarUrl ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img src={avatarUrl} alt="Avatar" className="h-16 w-16 rounded-full object-cover" />
        ) : (
          <Avatar name={emp.full_name} className="h-16 w-16 text-lg" />
        )}
        <div>
          <input
            ref={fileInput}
            type="file"
            accept="image/*"
            className="hidden"
            onChange={(e) => {
              const f = e.target.files?.[0];
              if (f) void onAvatar(f);
            }}
          />
          <Button variant="secondary" onClick={() => fileInput.current?.click()}>
            Cambiar foto
          </Button>
          <p className="mt-1 text-xs text-ink-soft">JPG, PNG o WEBP · máx. 4 MB</p>
        </div>
      </Card>

      <Card className="p-5">
        <FormSection title="Datos personales">
          <div className="grid gap-4 sm:grid-cols-2">
            <TextField label="Nombre" value={form.first_name} onChange={(v) => setForm({ ...form, first_name: v })} />
            <TextField label="Apellidos" value={form.last_name} onChange={(v) => setForm({ ...form, last_name: v })} />
            <TextField label="Teléfono" value={form.phone_personal} onChange={(v) => setForm({ ...form, phone_personal: v })} />
            <TextField label="Ciudad" value={form.city} onChange={(v) => setForm({ ...form, city: v })} />
            <TextField label="Dirección" value={form.address} onChange={(v) => setForm({ ...form, address: v })} className="sm:col-span-2" />
            <TextField label="Código postal" value={form.postal_code} onChange={(v) => setForm({ ...form, postal_code: v })} />
            <TextField label="Provincia" value={form.province} onChange={(v) => setForm({ ...form, province: v })} />
          </div>
        </FormSection>
        <div className="mt-5 flex justify-end">
          <Button onClick={save} disabled={saving}>{saving ? "Guardando…" : "Guardar cambios"}</Button>
        </div>
      </Card>
    </div>
  );
}
