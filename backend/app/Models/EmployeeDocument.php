<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Documento de un empleado (subido a disco).
 *
 * @property string $id
 * @property string $employee_id
 * @property string $name
 * @property string $file_path
 */
class EmployeeDocument extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = ['employee_id', 'name', 'type', 'file_path', 'visible_to_employee'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['visible_to_employee' => 'boolean'];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
