<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Titulación, curso, certificado o conocimiento de un empleado (Formación).
 *
 * @property string $id
 * @property string $employee_id
 */
class EmployeeQualification extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'employee_id', 'type', 'name', 'institution', 'date_obtained', 'expiry_date', 'document_path', 'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['date_obtained' => 'date:Y-m-d', 'expiry_date' => 'date:Y-m-d'];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
