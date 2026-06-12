<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><style>
  * { font-family: DejaVu Sans, sans-serif; color: #1b2733; }
  body { font-size: 10px; margin: 0; }
  h1 { color: #0F2756; font-size: 16px; margin: 0 0 2px; }
  .sub { color: #5b6b7d; font-size: 10px; margin-bottom: 12px; }
  table { width: 100%; border-collapse: collapse; }
  th { background: #0F2756; color: #fff; padding: 6px 8px; text-align: left; font-size: 9px; }
  td { padding: 5px 8px; border-bottom: 1px solid #e4e9f0; font-size: 10px; }
  .foot { margin-top: 14px; font-size: 8px; color: #9aa3b0; }
</style></head><body>
  <h1>Listado de socios — {{ $entity->name }}</h1>
  <div class="sub">{{ $members->count() }} socios · generado el {{ now()->format('d/m/Y') }}</div>
  <table>
    <thead><tr><th>Nº</th><th>Nombre</th><th>Tipo</th><th>Estado</th><th>Email</th></tr></thead>
    <tbody>
      @foreach ($members as $m)
        <tr>
          <td>{{ $m->member_number ?? '—' }}</td>
          <td>{{ $m->fullName() }}</td>
          <td>{{ $m->memberType?->name ?? '—' }}</td>
          <td>{{ ucfirst(str_replace('_',' ', $m->status)) }}</td>
          <td>{{ $m->email ?? '—' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
  <div class="foot">Documento generado por Gestioname · {{ $entity->name }}</div>
</body></html>
