<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Asiento contable. Sus líneas deben cuadrar (suma debe = suma haber).
 *
 * @property string $id
 * @property Carbon $date
 * @property string $description
 */
class JournalEntry extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = ['date', 'description', 'reference', 'entity_id', 'company_id', 'created_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d'];
    }

    /** @return HasMany<JournalLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
