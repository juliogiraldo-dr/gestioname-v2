<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ajustes del recordatorio automático de cuota de una entidad.
 *
 * @property string $entity_id
 * @property bool $enabled
 * @property int $days_before
 * @property string $subject
 * @property string $body
 */
class QuotaReminderSetting extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'entity_id', 'enabled', 'days_before', 'subject', 'body', 'last_run_on',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'days_before' => 'integer',
            'last_run_on' => 'date',
        ];
    }

    /** @return BelongsTo<Entity, $this> */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }
}
