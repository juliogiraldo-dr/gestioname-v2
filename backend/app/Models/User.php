<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Usuario del tenant. Vive en el schema del tenant (no en `public`).
 *
 * Clave primaria UUID (ordenado): coincide con el contrato API y evita exponer
 * identificadores secuenciales. El subdominio sigue identificando al tenant.
 *
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string|null $password
 * @property string|null $dni Cifrado en BD (cast encrypted).
 * @property string|null $phone
 * @property string|null $code_fichaje Código de 8 dígitos para el reloj kiosk.
 * @property string|null $avatar
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasUuids, Notifiable;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'email',
        'password',
        'dni',
        'phone',
        'code_fichaje',
        'avatar',
        'active',
        'last_login_at',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
        'dni',
    ];

    /** @var array<string, mixed> */
    protected $attributes = ['active' => true];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'dni' => 'encrypted',
            'active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }
}
