<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tipo de ausencia/presencia de un convenio.
 *
 * @property string $id
 * @property string $agreement_id
 * @property string $name
 * @property string $type
 * @property string $count_in
 * @property bool $subtracts_vacation
 * @property bool $requires_document
 * @property bool $visible_to_employee
 * @property int|null $max_days
 * @property float|null $max_hours
 */
class AgreementLeaveType extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'agreement_id',
        'name',
        'type',
        'count_in',
        'subtracts_vacation',
        'requires_document',
        'visible_to_employee',
        'max_days',
        'max_hours',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtracts_vacation' => 'boolean',
            'requires_document' => 'boolean',
            'visible_to_employee' => 'boolean',
            'max_days' => 'integer',
            'max_hours' => 'float',
        ];
    }

    /** @return BelongsTo<Agreement, $this> */
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }
}
