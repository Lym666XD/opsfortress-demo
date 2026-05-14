<?php

declare(strict_types=1);

namespace App\Domain\Shared\Audit\Models;

use App\Domain\Shared\Tenancy\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tamper-evident audit record. Append-only by convention; do NOT update or delete
 * in application code. Use AuditService::record() to write — never instantiate
 * directly outside the service.
 */
class AuditEvent extends Model
{
    use BelongsToTenant;

    protected $table = 'audit_events';

    protected $fillable = [
        'tenant_id',
        'business_id',
        'user_id',
        'subject_type',
        'subject_id',
        'anchor',
        'event_name',
        'hash',
        'previous_hash',
        'payload',
        'occurred_at',
    ];

    protected $casts = [
        // FK int casts — PG's PDO driver returns BIGINT as string.
        'tenant_id' => 'integer',
        'business_id' => 'integer',
        'user_id' => 'integer',
        'subject_id' => 'integer',
        'payload' => 'array',
        'occurred_at' => 'immutable_datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
