<?php

declare(strict_types=1);

namespace App\Domain\Whs\Activities\Models;

use App\Domain\OpsFortress\Businesses\Models\Business;
use App\Domain\OpsFortress\Workplaces\Models\Workplace;
use App\Domain\Shared\Tenancy\BelongsToTenant;
use App\Domain\Whs\TaskPacks\Models\TaskPack;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Runtime-layer model. Per architecture record §4.6:
 *   "Everything a worker does = an Activity:
 *    Sign-in, Inductions, Pre-starts, Permits, Training, Reporting"
 *
 * The `payload` JSON column is intentionally a raw-submission snapshot only —
 * business queries / scoring / dashboards read from normalized child tables
 * (prestart_responses, training_attempts, etc.) once those are added in Phase 1.
 */
class Activity extends Model
{
    use BelongsToTenant;

    /**
     * Immutability guards:
     *   - payload: append-only per architecture record §4.6 (audit hardening A1)
     *   - blockchain_id: immutable once set per Kevin's spec (K1)
     */
    protected static function booted(): void
    {
        static::updating(function (self $activity): void {
            if ($activity->isDirty('payload')) {
                throw new \RuntimeException(
                    'Activity.payload is immutable after creation. '.
                    'Per architecture record §4.6, submitted records are append-only. '.
                    'To record corrections, create a new superseding Activity row.',
                );
            }

            $originalBcId = $activity->getOriginal('blockchain_id');
            if ($originalBcId !== null && $activity->isDirty('blockchain_id')) {
                throw new \RuntimeException(
                    'Activity.blockchain_id is immutable once set (K1).',
                );
            }
        });
    }

    protected $table = 'activities';

    /**
     * K3: blockchain_id is internal-only per Kevin's 2026-05-13 spec.
     */
    protected $hidden = [
        'blockchain_id',
    ];

    protected $fillable = [
        'tenant_id',
        'business_id',
        'workplace_id',
        'user_id',
        'task_pack_id',
        'activity_type',
        'status',
        'started_at',
        'completed_at',
        'blockchain_id',
        'original_hash',
        'payload',
    ];

    protected $casts = [
        // FK int casts — PG's PDO driver returns BIGINT as string.
        'tenant_id' => 'integer',
        'business_id' => 'integer',
        'workplace_id' => 'integer',
        'user_id' => 'integer',
        'task_pack_id' => 'integer',
        'started_at' => 'immutable_datetime',
        'completed_at' => 'immutable_datetime',
        'payload' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function workplace(): BelongsTo
    {
        return $this->belongsTo(Workplace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function taskPack(): BelongsTo
    {
        return $this->belongsTo(TaskPack::class);
    }
}
