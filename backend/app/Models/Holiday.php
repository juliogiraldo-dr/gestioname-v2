<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Festivo. Global del tenant; aplica a los centros asociados (o a todos si no hay ninguno).
 *
 * @property string $id
 * @property string $name
 * @property string $type
 * @property bool $repeatable
 * @property int|null $day_of_year
 * @property Carbon|null $date
 * @property string|null $province
 * @property string|null $locality
 */
class Holiday extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'type',
        'repeatable',
        'day_of_year',
        'date',
        'province',
        'locality',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'repeatable' => 'boolean',
            'day_of_year' => 'integer',
            'date' => 'date:Y-m-d',
        ];
    }

    /** @return BelongsToMany<WorkCenter, $this> */
    public function workCenters(): BelongsToMany
    {
        return $this->belongsToMany(WorkCenter::class, 'holiday_work_centers', 'holiday_id', 'work_center_id');
    }
}
