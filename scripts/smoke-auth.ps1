# Prueba manual del flujo de autenticación del tenant `demo`.
#   Uso:  .\scripts\smoke-auth.ps1
# Requiere el stack levantado (docker compose up -d) y el tenant demo creado.

$ErrorActionPreference = 'Stop'
$base = 'http://localhost:8000/api/v1'
$host_ = 'demo.localhost'              # identifica el tenant por subdominio
$email = 'admin@demo.gestioname.app'
$password = 'password'

function Post($path, $json) {
    $tmp = New-TemporaryFile
    Set-Content -Path $tmp -Value $json -Encoding ascii -NoNewline
    $out = curl.exe -s -X POST "$base$path" -H "Host: $host_" -H 'Content-Type: application/json' -H 'Accept: application/json' --data "@$tmp"
    Remove-Item $tmp
    return $out
}
function Get($path, $token) {
    return curl.exe -s "$base$path" -H "Host: $host_" -H 'Accept: application/json' -H "Authorization: Bearer $token"
}

Write-Host "`n=== 1) LOGIN ===" -ForegroundColor Cyan
$login = Post '/auth/login' "{`"email`":`"$email`",`"password`":`"$password`"}"
$login
$token = ($login | ConvertFrom-Json).data.token

Write-Host "`n=== 2) GET /auth/me (con el token) ===" -ForegroundColor Cyan
Get '/auth/me' $token

Write-Host "`n=== 3) LOGIN MAL (debe ser 422) ===" -ForegroundColor Cyan
Post '/auth/login' "{`"email`":`"$email`",`"password`":`"incorrecta`"}"

Write-Host "`n=== 4) MAGIC LINK (revisa el email en Mailpit: http://localhost:8025) ===" -ForegroundColor Cyan
Post '/auth/magic-link' "{`"email`":`"$email`"}"

Write-Host "`n=== 5) REFRESH (rota el token) ===" -ForegroundColor Cyan
$refresh = curl.exe -s -X POST "$base/auth/refresh" -H "Host: $host_" -H 'Accept: application/json' -H "Authorization: Bearer $token"
$refresh
$new = ($refresh | ConvertFrom-Json).data.token

Write-Host "`n=== 6) LOGOUT ===" -ForegroundColor Cyan
curl.exe -s -X POST "$base/auth/logout" -H "Host: $host_" -H 'Accept: application/json' -H "Authorization: Bearer $new"

Write-Host "`n`nListo. Token tras login: $token" -ForegroundColor Green
