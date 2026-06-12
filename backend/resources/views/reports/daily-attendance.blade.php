<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #1f2937; }
        h1 { font-size: 16px; color: #0F2756; margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #0F2756; color: #fff; padding: 5px 6px; text-align: left; font-size: 10px; }
        td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
        .foot { margin-top: 10px; font-size: 8px; color: #9aa3b0; }
    </style>
</head>
<body>
    <h1>Informe diario de fichajes — {{ $date }}</h1>
    <table>
        <thead>
            <tr><th>Empleado</th><th>Hito</th><th>Hora</th><th>Método</th></tr>
        </thead>
        <tbody>
            @forelse ($attendances as $att)
                <tr>
                    <td>{{ $att->employee?->fullName() }}</td>
                    <td>{{ $att->milestone?->name }}</td>
                    <td>{{ $att->clocked_at->format('H:i') }}</td>
                    <td>{{ $att->method }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Sin fichajes en la fecha.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="foot">Documento generado por Gestioname &middot; Datarecover S.L.</div>
</body>
</html>
