<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Hito de fichaje (entrada/salida) de una empresa.
 *
 * @property string $id
 * @property string $company_id
 * @property string $name
 * @property string|null $description
 * @property string $color
 * @property string $type
 * @property bool $show_in_report
 * @property bool $active
 */
class AttendanceMilestone extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'color',
        'type',
        'show_in_report',
        'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'show_in_report' => 'boolean',
            'active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsToMany<WorkCenter, $this> */
    public function workCenters(): BelongsToMany
    {
        return $this->belongsToMany(WorkCenter::class, 'milestone_work_centers', 'milestone_id', 'work_center_id');
    }
}
