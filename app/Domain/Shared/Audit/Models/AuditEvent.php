<?php

declare(strict_types=1);

namespace App\Domain\Shared\Audit\Models;

use App\Domain\Shared\Context\BelongsToAccount;
use App\Domain\Whs\Runtime\Models\WorkerTaskSession;
use App\Models\Concerns\UsesUuidPrimaryKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tamper-evident audit record. Append-only by convention; write via
 * AuditService so hash_sequence and event_hash stay consistent.
 */
class AuditEvent extends Model
{
    use BelongsToAccount, UsesUuidPrimaryKey;

    protected $table = 'audit_events';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'hash_sequence' => 'integer',
            'event_payload' => 'array',
            'occurred_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workerTaskSession(): BelongsTo
    {
        return $this->belongsTo(WorkerTaskSession::class);
    }
}
