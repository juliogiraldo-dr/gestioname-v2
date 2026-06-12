<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Token de acceso por magic link. Vive en el schema del tenant.
 * Solo se persiste el hash sha256 del token; el valor en claro únicamente viaja en el
 * email enviado al usuario.
 *
 * @property int $id
 * @property string $email
 * @property string $token_hash
 * @property Carbon $expires_at
 * @property Carbon|null $used_at
 */
class MagicLinkToken extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'email',
        'token_hash',
        'expires_at',
        'used_at',
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

    public function isUsable(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }
}
