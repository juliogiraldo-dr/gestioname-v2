<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\DownloadTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Zona pública de descarga (sin autenticación, dentro del tenant). Sirve un fichero
 * a partir de un token de un solo uso válido 72 h y deja registro de la descarga.
 */
class PublicDownloadController extends Controller
{
    public function __construct(private readonly DownloadTokenService $tokens) {}

    public function show(Request $request, string $token): StreamedResponse
    {
        $download = $this->tokens->findUsable($token);

        // 410 Gone: enlace inexistente, ya usado o caducado.
        abort_if($download === null, 410, 'Este enlace de descarga no es válido o ya ha caducado.');
        abort_unless(Storage::disk($download->disk)->exists($download->file_path), 404);

        // Marca el uso (un solo uso) y registra la descarga antes de servir el fichero.
        $download->update([
            'used_at' => now(),
            'downloaded_ip' => $request->ip(),
        ]);

        return Storage::disk($download->disk)->download($download->file_path, $download->filename);
    }
}
