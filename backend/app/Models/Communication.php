<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de una comunicación masiva enviada (socios o empleados).
 *
 * @property string $audience
 * @property string $subject
 * @property int $recipients_count
 */
class Communication extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'audience', 'entity_id', 'subject', 'body', 'filters',
        'recipients_count', 'trigger', 'sent_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['filters' => 'array'];
    }

    /** @return BelongsTo<Entity, $this> */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /** @return BelongsTo<User, $this> */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
