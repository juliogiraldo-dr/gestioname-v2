<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Empresa del tenant. Puede pertenecer a un grupo de empresas.
 *
 * @property string $id
 * @property string|null $company_group_id
 * @property string $name
 * @property string $cif
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $logo_path
 */
class Company extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'company_group_id',
        'name',
        'cif',
        'address',
        'phone',
        'email',
        'logo_path',
    ];

    /** @return BelongsTo<CompanyGroup, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(CompanyGroup::class, 'company_group_id');
    }

    /** @return HasMany<WorkCenter, $this> */
    public function workCenters(): HasMany
    {
        return $this->hasMany(WorkCenter::class);
    }

    /** @return HasMany<AttendanceMilestone, $this> */
    public function milestones(): HasMany
    {
        return $this->hasMany(AttendanceMilestone::class);
    }
}
