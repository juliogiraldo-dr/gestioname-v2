<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><style>
  * { font-family: DejaVu Sans, sans-serif; margin: 0; }
  body { font-size: 10px; }
  .card { width: 242px; height: 153px; background: #0F2756; color: #fff; padding: 14px 16px; box-sizing: border-box; }
  .ent { font-size: 12px; font-weight: bold; color: #68DFB9; }
  .nm { font-size: 15px; font-weight: bold; margin-top: 18px; }
  .row { margin-top: 8px; font-size: 10px; color: #cdd6e2; }
  .row b { color: #fff; }
  .badge { display: inline-block; margin-top: 10px; background: #5EB8D0; color: #0F2756; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; }
</style></head><body>
  <div class="card">
    <div class="ent">{{ $entity->name }}</div>
    <div class="nm">{{ $member->fullName() }}</div>
    <div class="row">Nº socio: <b>{{ $member->member_number ?? '—' }}</b></div>
    <div class="row">Tipo: <b>{{ $member->memberType?->name ?? '—' }}</b></div>
    <span class="badge">SOCIO {{ $entity->fiscal_year ?? now()->year }}</span>
  </div>
</body></html>
