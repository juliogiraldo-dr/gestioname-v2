<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><style>
  * { font-family: DejaVu Sans, sans-serif; color: #1b2733; }
  body { font-size: 11px; margin: 0; }
  .band { background: #0F2756; color: #fff; padding: 16px 28px; }
  .band h1 { margin: 0; font-size: 17px; }
  .wrap { padding: 24px 28px; }
  h2 { color: #0F2756; font-size: 13px; border-bottom: 2px solid #5EB8D0; padding-bottom: 4px; margin: 18px 0 8px; }
  table { width: 100%; border-collapse: collapse; }
  td { padding: 5px 0; font-size: 11px; }
  td.k { color: #5b6b7d; width: 35%; }
  .pay th { background: #0F2756; color: #fff; padding: 5px 8px; text-align: left; font-size: 10px; }
  .pay td { padding: 5px 8px; border-bottom: 1px solid #e4e9f0; }
  .foot { margin-top: 24px; font-size: 9px; color: #9aa3b0; }
</style></head><body>
  <div class="band"><h1>{{ $member->fullName() }}</h1></div>
  <div class="wrap">
    <h2>Datos del socio</h2>
    <table>
      <tr><td class="k">Entidad</td><td>{{ $entity->name }}</td></tr>
      <tr><td class="k">Nº de socio</td><td>{{ $member->member_number ?? '—' }}</td></tr>
      <tr><td class="k">Tipo</td><td>{{ $member->memberType?->name ?? '—' }}</td></tr>
      <tr><td class="k">Estado</td><td>{{ ucfirst(str_replace('_',' ', $member->status)) }}</td></tr>
      <tr><td class="k">DNI</td><td>{{ $member->dni ?? '—' }}</td></tr>
      <tr><td class="k">Email</td><td>{{ $member->email ?? '—' }}</td></tr>
      <tr><td class="k">Teléfono</td><td>{{ $member->phone ?? '—' }}</td></tr>
      <tr><td class="k">Alta</td><td>{{ $member->date_join?->format('d/m/Y') ?? '—' }}</td></tr>
    </table>

    <h2>Historial de pagos</h2>
    @if ($payments->isEmpty())
      <p style="color:#5b6b7d;">Sin pagos registrados.</p>
    @else
      <table class="pay">
        <thead><tr><th>Ejercicio</th><th>Importe</th><th>Estado</th><th>Método</th><th>Fecha</th></tr></thead>
        <tbody>
          @foreach ($payments as $p)
            <tr><td>{{ $p->year }}</td><td>{{ number_format($p->amount, 2, ',', '.') }} €</td><td>{{ ucfirst($p->status) }}</td><td>{{ ucfirst($p->payment_method ?? '—') }}</td><td>{{ $p->payment_date?->format('d/m/Y') ?? '—' }}</td></tr>
          @endforeach
        </tbody>
      </table>
    @endif
    <div class="foot">Documento generado por Gestioname · {{ $entity->name }}</div>
  </div>
</body></html>
