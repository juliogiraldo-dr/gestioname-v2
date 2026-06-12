<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f6fa;font-family:'Segoe UI',Helvetica,Arial,sans-serif;color:#1b2733;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fa;padding:28px 12px;">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 4px 16px rgba(15,39,86,0.08);">
        <tr><td style="background:#0F2756;padding:22px 28px;">
          <span style="color:#fff;font-size:18px;font-weight:600;">{{ $appName ?? 'Gestioname' }}</span>
        </td></tr>
        <tr><td style="padding:28px;">
          @isset($heading)<h1 style="margin:0 0 14px;font-size:20px;color:#0F2756;">{{ $heading }}</h1>@endisset
          @foreach (($lines ?? []) as $line)
            <p style="margin:0 0 12px;font-size:14px;line-height:1.6;color:#1b2733;">{{ $line }}</p>
          @endforeach
          @isset($actionUrl)
            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:22px 0;"><tr><td style="border-radius:8px;background:#68DFB9;">
              <a href="{{ $actionUrl }}" style="display:inline-block;padding:12px 28px;font-size:15px;font-weight:600;color:#0F2756;text-decoration:none;">{{ $actionText ?? 'Continuar' }}</a>
            </td></tr></table>
            <p style="margin:0;font-size:12px;color:#5b6b7d;word-break:break-all;">O copia este enlace: {{ $actionUrl }}</p>
          @endisset
          @isset($outro)<p style="margin:18px 0 0;font-size:12px;color:#5b6b7d;">{{ $outro }}</p>@endisset
        </td></tr>
        <tr><td style="padding:16px 28px;border-top:1px solid #e4e9f0;">
          <p style="margin:0;font-size:11px;color:#9aa3b0;">{{ $appName ?? 'Gestioname' }} · Datarecover S.L.</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
