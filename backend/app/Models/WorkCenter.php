<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Centro de trabajo de una empresa.
 *
 * @property string $id
 * @property string $company_id
 * @property string $name
 * @property string|null $address
 * @property float|null $lat
 * @property float|null $lng
 * @property string $timezone
 */
class WorkCenter extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'company_id',
        'name',
        'address',
        'lat',
        'lng',
        'timezone',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsToMany<AttendanceMilestone, $this> */
    public function milestones(): BelongsToMany
    {
        return $this->belongsToMany(AttendanceMilestone::class, 'milestone_work_centers', 'work_center_id', 'milestone_id');
    }

    /** @return BelongsToMany<Holiday, $this> */
    public function holidays(): BelongsToMany
    {
        return $this->belongsToMany(Holiday::class, 'holiday_work_centers', 'work_center_id', 'holiday_id');
    }
}
