<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><style>
  * { font-family: DejaVu Sans, sans-serif; color: #1b2733; }
  body { font-size: 12px; margin: 0; }
  .band { background: #0F2756; color: #fff; padding: 18px 28px; }
  .band h1 { margin: 0; font-size: 18px; }
  .band p { margin: 2px 0 0; font-size: 11px; color: #bcd; }
  .wrap { padding: 28px; }
  .num { text-align: right; color: #5b6b7d; font-size: 11px; }
  table { width: 100%; border-collapse: collapse; margin-top: 16px; }
  td { padding: 8px 0; border-bottom: 1px solid #e4e9f0; font-size: 13px; }
  td.k { color: #5b6b7d; width: 40%; }
  .total { margin-top: 20px; background: #f4f6fa; padding: 16px 20px; border-radius: 8px; }
  .total .amt { font-size: 26px; font-weight: bold; color: #0F2756; }
  .foot { margin-top: 28px; font-size: 9px; color: #9aa3b0; text-align: center; }
</style></head><body>
  <div class="band">
    <h1>{{ $entity->name }}</h1>
    <p>{{ $entity->cif ?? '' }}{{ $entity->address ? ' · '.$entity->address : '' }}</p>
  </div>
  <div class="wrap">
    <p class="num">Recibo Nº {{ $number }} · {{ $payment->payment_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}</p>
    <h2 style="color:#0F2756;font-size:16px;">Recibo de pago de cuota</h2>
    <table>
      <tr><td class="k">Socio</td><td>{{ $member->fullName() }}{{ $member->member_number ? ' (Nº '.$member->member_number.')' : '' }}</td></tr>
      <tr><td class="k">Ejercicio</td><td>{{ $payment->year }}</td></tr>
      <tr><td class="k">Estado</td><td>{{ ucfirst($payment->status) }}</td></tr>
      <tr><td class="k">Método de pago</td><td>{{ ucfirst($payment->payment_method ?? '—') }}</td></tr>
    </table>
    <div class="total">
      <span style="color:#5b6b7d;font-size:11px;">Importe</span><br>
      <span class="amt">{{ number_format($payment->amount, 2, ',', '.') }} €</span>
    </div>
    <div class="foot">Documento generado por Gestioname · {{ $entity->name }}</div>
  </div>
</body></html>
