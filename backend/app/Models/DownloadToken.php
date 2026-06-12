<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Enlace público de descarga de un solo uso (válido 72 h). Ver migración.
 *
 * @property string $id
 * @property string $token_hash
 * @property string $disk
 * @property string $file_path
 * @property string $filename
 * @property string $kind
 * @property string|null $label
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $used_at
 */
class DownloadToken extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'token_hash', 'disk', 'file_path', 'filename', 'kind', 'label',
        'created_by', 'expires_at', 'used_at', 'downloaded_ip',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /** Un solo uso y dentro de la ventana de validez. */
    public function isUsable(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
