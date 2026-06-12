<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Nodo del organigrama de un centro de trabajo.
 *
 * @property string $id
 * @property string $work_center_id
 * @property string $employee_id
 * @property string|null $parent_id
 * @property bool $receives_notifications
 * @property int $sort_order
 */
class OrgChartNode extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'work_center_id', 'employee_id', 'parent_id', 'receives_notifications', 'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'receives_notifications' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<WorkCenter, $this> */
    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<OrgChartNode, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrgChartNode::class, 'parent_id');
    }

    /** @return HasMany<OrgChartNode, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(OrgChartNode::class, 'parent_id')->orderBy('sort_order');
    }
}
