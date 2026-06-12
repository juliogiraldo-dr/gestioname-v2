<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Grupo de empresas dentro de un tenant.
 *
 * @property string $id
 * @property string $name
 */
class CompanyGroup extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = ['name'];

    /** @return HasMany<Company, $this> */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
