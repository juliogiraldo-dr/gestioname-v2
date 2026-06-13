"use client";

import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import { api, ApiError } from "@/lib/api";
import { useActiveCompany } from "@/lib/company";
import { useToast } from "@/lib/toast";
import { useConfirm } from "@/lib/confirm";
import { Avatar, Badge, Button, Card, EmptyState, PageHeader, SelectField, Skeleton } from "@/components/ui";

type WorkCenter = { id: string; name: string };
type EmployeeOption = { id: string; full_name: string };

type OrgEmployee = {
  id: string;
  name: string;
  job_position: string | null;
  department: string | null;
  has_photo: boolean;
};

type OrgNode = {
  id: string;
  employee_id: string;
  parent_id: string | null;
  receives_notifications: boolean;
  employee: OrgEmployee | null;
  subordinates: number;
  children: OrgNode[];
};

/** Aplana el árbol para poder ofrecer cada nodo como posible "padre". */
function flatten(nodes: OrgNode[], acc: OrgNode[] = []): OrgNode[] {
  for (const n of nodes) {
    acc.push(n);
    if (n.children.length) flatten(n.children, acc);
  }
  return acc;
}

export default function OrganigramaPage() {
  const company = useActiveCompany();
  const companyId = company?.activeId ?? "";
  const toast = useToast();
  const confirm = useConfirm();

  const [centers, setCenters] = useState<WorkCenter[]>([]);
  const [centerId, setCenterId] = useState("");
  const [tree, setTree] = useState<OrgNode[] | null>(null);
  const [employees, setEmployees] = useState<EmployeeOption[]>([]);

  const [newEmployee, setNewEmployee] = useState("");
  const [newParent, setNewParent] = useState("");
  const [adding, setAdding] = useState(false);

  // Centros de trabajo de la empresa activa + empleados activos para el formulario.
  useEffect(() => {
    if (!companyId) return;
    void (async () => {
      try {
        const [c, e] = await Promise.all([
          api<{ data: WorkCenter[] }>(`/companies/${companyId}/work-centers`),
          api<{ data: EmployeeOption[] }>(`/employees?company_id=${companyId}&active=1`),
        ]);
        setCenters(c.data);
        setEmployees(e.data);
        setCenterId((prev) => prev || c.data[0]?.id || "");
      } catch (err) {
        toast.error(err instanceof ApiError ? err.message : "No se pudieron cargar los centros.");
      }
    })();
  }, [companyId, toast]);

  // Carga el árbol del centro. No hace setState síncrono: el estado se fija siempre tras
  // un await (regla react-hooks/set-state-in-effect). `tree === null` indica "cargando".
  const loadTree = useCallback(async (wcId: string) => {
    try {
      const res = await api<{ data: OrgNode[] }>(`/org-chart/${wcId}`);
      setTree(res.data);
    } catch (err) {
      setTree([]);
      toast.error(err instanceof ApiError ? err.message : "No se pudo cargar el organigrama.");
    }
  }, [toast]);

  // Al cambiar de centro, vuelve a "cargando" (tras await) y recarga el árbol.
  useEffect(() => {
    if (!centerId) {
      return;
    }
    void (async () => {
      await Promise.resolve();
      setTree(null);
      await loadTree(centerId);
    })();
  }, [centerId, loadTree]);

  const nodes = tree ?? [];
  const allNodes = flatten(nodes);

  async function addNode() {
    if (!centerId || !newEmployee) {
      toast.error("Selecciona un centro y un empleado.");
      return;
    }
    setAdding(true);
    try {
      await api("/org-chart/nodes", {
        method: "POST",
        body: {
          work_center_id: centerId,
          employee_id: newEmployee,
          ...(newParent ? { parent_id: newParent } : {}),
        },
      });
      toast.success("Empleado añadido al organigrama.");
      setNewEmployee("");
      setNewParent("");
      await loadTree(centerId);
    } catch (err) {
      toast.error(err instanceof ApiError ? err.message : "No se pudo añadir al organigrama.");
    } finally {
      setAdding(false);
    }
  }

  async function removeNode(node: OrgNode) {
    const ok = await confirm({
      title: "Quitar del organigrama",
      message: `¿Quitar a ${node.employee?.name ?? "este empleado"} del organigrama?`,
      confirmLabel: "Quitar",
    });
    if (!ok) return;
    try {
      await api(`/org-chart/nodes/${node.id}`, { method: "DELETE" });
      toast.success("Empleado quitado del organigrama.");
      await loadTree(centerId);
    } catch (err) {
      toast.error(err instanceof ApiError ? err.message : "No se pudo quitar del organigrama.");
    }
  }

  if (!company) {
    return (
      <div>
        <PageHeader title="Organigrama" subtitle="Estructura jerárquica por centro de trabajo" />
        <EmptyState title="Sin empresa" message="Selecciona una empresa para ver el organigrama." />
      </div>
    );
  }

  const centerOptions: ReadonlyArray<readonly [string, string]> =
    centers.length === 0
      ? [["", "Sin centros de trabajo"]]
      : centers.map((c) => [c.id, c.name] as const);

  const employeeOptions: ReadonlyArray<readonly [string, string]> = [
    ["", "Selecciona empleado…"],
    ...employees.map((e) => [e.id, e.full_name] as const),
  ];

  const parentOptions: ReadonlyArray<readonly [string, string]> = [
    ["", "Sin responsable (raíz)"],
    ...allNodes
      .filter((n) => n.employee)
      .map((n) => [n.id, n.employee!.name] as const),
  ];

  return (
    <div>
      <PageHeader title="Organigrama" subtitle="Estructura jerárquica por centro de trabajo" />

      <Card className="mb-6 p-5">
        <SelectField
          label="Centro de trabajo"
          value={centerId}
          onChange={setCenterId}
          options={centerOptions}
          className="max-w-sm"
        />
      </Card>

      {/* Añadir al organigrama */}
      <Card className="mb-6 p-5">
        <h3 className="mb-4 text-base font-semibold text-primary">Añadir al organigrama</h3>
        <div className="grid gap-4 sm:grid-cols-3">
          <SelectField label="Empleado" value={newEmployee} onChange={setNewEmployee} options={employeeOptions} />
          <SelectField label="Responsable" value={newParent} onChange={setNewParent} options={parentOptions} />
          <div className="flex items-end">
            <Button onClick={addNode} disabled={adding || !centerId || !newEmployee}>
              {adding ? "Añadiendo…" : "Añadir"}
            </Button>
          </div>
        </div>
      </Card>

      {/* Árbol */}
      {tree === null ? (
        <Skeleton rows={5} />
      ) : nodes.length === 0 ? (
        <EmptyState
          title="Organigrama vacío"
          message="Este centro de trabajo no tiene ningún empleado en el organigrama todavía."
        />
      ) : (
        <div className="space-y-3">
          {nodes.map((n) => (
            <OrgNodeCard key={n.id} node={n} depth={0} onRemove={removeNode} />
          ))}
        </div>
      )}
    </div>
  );
}

function OrgNodeCard({
  node,
  depth,
  onRemove,
}: {
  node: OrgNode;
  depth: number;
  onRemove: (n: OrgNode) => void;
}) {
  const emp = node.employee;
  return (
    <div className={depth > 0 ? "ml-6 border-l border-line pl-4" : ""}>
      <Card className="flex items-center gap-3 p-4">
        {emp ? (
          <Link
            href={`/admin/empleados?employee=${emp.id}`}
            className="flex flex-1 items-center gap-3 outline-none"
          >
            <Avatar name={emp.name} />
            <div className="min-w-0">
              <p className="truncate font-semibold text-ink hover:text-primary">{emp.name}</p>
              <p className="truncate text-sm text-ink-soft">
                {[emp.job_position, emp.department].filter(Boolean).join(" · ") || "—"}
              </p>
            </div>
          </Link>
        ) : (
          <div className="flex flex-1 items-center gap-3">
            <Avatar name="?" />
            <p className="text-sm text-ink-soft">Empleado no disponible</p>
          </div>
        )}
        <div className="flex shrink-0 items-center gap-2">
          {node.subordinates > 0 && (
            <Badge tone="info">
              {node.subordinates} subordinado{node.subordinates === 1 ? "" : "s"}
            </Badge>
          )}
          <Button variant="ghost" onClick={() => onRemove(node)}>
            Quitar
          </Button>
        </div>
      </Card>

      {node.children.length > 0 && (
        <div className="mt-3 space-y-3">
          {node.children.map((child) => (
            <OrgNodeCard key={child.id} node={child} depth={depth + 1} onRemove={onRemove} />
          ))}
        </div>
      )}
    </div>
  );
}
