<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DownloadToken;
use Illuminate\Support\Str;

/**
 * Genera enlaces públicos de descarga de un solo uso (válidos 72 h). El token en
 * claro solo existe en el momento de crearlo; en BD se guarda su hash sha256.
 */
class DownloadTokenService
{
    public const TTL_HOURS = 72;

    /**
     * Crea un token para un fichero ya almacenado en disco.
     *
     * @return array{token: string, model: DownloadToken}
     */
    public function create(
        string $filePath,
        string $filename,
        string $kind = 'documento',
        ?string $label = null,
        ?string $createdBy = null,
        string $disk = 'local',
    ): array {
        $raw = Str::random(48);

        $model = DownloadToken::create([
            'token_hash' => hash('sha256', $raw),
            'disk' => $disk,
            'file_path' => $filePath,
            'filename' => $filename,
            'kind' => $kind,
            'label' => $label,
            'created_by' => $createdBy,
            'expires_at' => now()->addHours(self::TTL_HOURS),
        ]);

        return ['token' => $raw, 'model' => $model];
    }

    /** Localiza un token válido (no usado y no caducado) por su valor en claro. */
    public function findUsable(string $rawToken): ?DownloadToken
    {
        $token = DownloadToken::where('token_hash', hash('sha256', $rawToken))->first();

        return $token !== null && $token->isUsable() ? $token : null;
    }
}
