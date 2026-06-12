<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de comportamiento (felicitación/amonestación/sanción) de un empleado.
 *
 * @property string $id
 * @property string $employee_id
 */
class EmployeeBehaviorRecord extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'employee_id', 'type', 'date', 'description', 'document_path', 'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d'];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
