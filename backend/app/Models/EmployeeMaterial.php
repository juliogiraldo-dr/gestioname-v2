<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Material cedido a un empleado.
 *
 * @property string $id
 * @property string $employee_id
 */
class EmployeeMaterial extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'employee_id', 'name', 'serial_number', 'delivery_date', 'return_date', 'status', 'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['delivery_date' => 'date:Y-m-d', 'return_date' => 'date:Y-m-d'];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
