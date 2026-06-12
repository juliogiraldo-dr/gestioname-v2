<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 10px; color: #1f2937; margin: 0; }
        h1 { font-size: 16px; color: #0F2756; margin: 0 0 2px; }
        .period { color: #5b6472; font-size: 10px; margin-bottom: 12px; }
        .employee { margin-bottom: 22px; page-break-inside: avoid; }
        .employee h2 { font-size: 12px; color: #0F2756; margin: 0 0 1px; border-bottom: 2px solid #5EB8D0; padding-bottom: 3px; }
        .meta { color: #5b6472; font-size: 9px; margin: 2px 0 6px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #0F2756; color: #fff; font-size: 9px; padding: 4px 5px; text-align: left; }
        td { padding: 3px 5px; border-bottom: 1px solid #e5e7eb; font-size: 9px; }
        tr.total td { font-weight: bold; background: #f1f5f9; border-top: 2px solid #0F2756; }
        .num { text-align: right; }
        .foot { margin-top: 6px; font-size: 8px; color: #9aa3b0; }
    </style>
</head>
<body>
    <h1>Registro de jornada</h1>
    <div class="period">
        Art. 34.9 del Estatuto de los Trabajadores &middot; Periodo {{ $meta['date_from'] }} a {{ $meta['date_to'] }}
        &middot; Generado el {{ $meta['generated_at'] }}
    </div>

    @foreach ($employees as $employee)
        @php($opts = $meta['options'])
        <div class="employee">
            <h2>{{ $employee['employee']['full_name'] }}</h2>
            <div class="meta">
                @if ($employee['employee']['job_position']){{ $employee['employee']['job_position'] }} &middot; @endif
                @if ($opts['include_work_center'] && $employee['employee']['work_center']){{ $employee['employee']['work_center'] }}@endif
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Horario</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th class="num">Previstas</th>
                        <th class="num">Realizadas</th>
                        <th class="num">Sobretiempo</th>
                        @if ($opts['include_delays'])<th class="num">Retraso</th>@endif
                        @if ($opts['include_method'])<th>Método</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employee['days'] as $day)
                        <tr>
                            <td>{{ $day['date'] }}</td>
                            <td>{{ $day['template'] ?? '—' }}</td>
                            <td>{{ $day['first_in'] ?? '—' }}</td>
                            <td>{{ $day['last_out'] ?? '—' }}</td>
                            <td class="num">{{ $fmt($day['expected']) }}</td>
                            <td class="num">{{ $fmt($day['worked']) }}</td>
                            <td class="num">{{ $fmt($day['overtime']) }}</td>
                            @if ($opts['include_delays'])<td class="num">{{ $day['delay_minutes'] }} min</td>@endif
                            @if ($opts['include_method'])<td>{{ collect($day['entries'])->pluck('method')->unique()->implode(', ') ?: '—' }}</td>@endif
                        </tr>
                    @empty
                        <tr><td colspan="9">Sin fichajes en el periodo.</td></tr>
                    @endforelse
                    <tr class="total">
                        <td colspan="4">TOTAL</td>
                        <td class="num">{{ $fmt($employee['totals']['expected']) }}</td>
                        <td class="num">{{ $fmt($employee['totals']['worked']) }}</td>
                        <td class="num">{{ $fmt($employee['totals']['overtime']) }}</td>
                        @if ($opts['include_delays'])<td class="num">{{ $employee['totals']['delay_minutes'] }} min</td>@endif
                        @if ($opts['include_method'])<td></td>@endif
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach

    <div class="foot">Documento generado por Gestioname &middot; Datarecover S.L.</div>
</body>
</html>
