<?php

declare(strict_types=1);

namespace App\Domain\Whs\Submissions\Models;

use App\Domain\OpsFortress\Businesses\Models\Business;
use App\Domain\OpsFortress\Workplaces\Models\Workplace;
use App\Domain\Shared\Tenancy\BelongsToTenant;
use App\Domain\Whs\Activities\Models\Activity;
use App\Domain\Whs\TaskPacks\Models\TaskPack;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Runtime-layer model. Per architecture record §4.6:
 *   "All submitted records are immutable — corrections create new
 *    superseding records (INSERT only, no UPDATE/DELETE)".
 *
 * Enforcement of immutability is via Policy + repository pattern, not at
 * the model level (Eloquent has no built-in append-only mode).
 *
 * `payload` is a raw-submission snapshot for dispute traceability —
 * never used for business queries (those read normalized child tables).
 */
class Submission extends Model
{
    use BelongsToTenant;

    /**
     * Immutability guards:
     *   - payload: append-only per architecture record §4.6 (audit hardening A1)
     *   - blockchain_id: immutable once set per Kevin's spec (K1)
     *
     * Guards against the AppSheet-era anti-pattern of mutating in-place
     * JSON snapshots and then claiming an audit trail exists.
     */
    protected static function booted(): void
    {
        static::updating(function (self $submission): void {
            if ($submission->isDirty('payload')) {
                throw new \RuntimeException(
                    'Submission.payload is immutable after creation. '.
                    'Per architecture record §4.6, submitted records are append-only. '.
                    'To record corrections, create a new superseding Submission row.',
                );
            }

            $originalBcId = $submission->getOriginal('blockchain_id');
            if ($originalBcId !== null && $submission->isDirty('blockchain_id')) {
                throw new \RuntimeException(
                    'Submission.blockchain_id is immutable once set (K1).',
                );
            }
        });
    }

    protected $table = 'submissions';

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
        'activity_id',
        'submission_type',
        'status',
        'score',
        'critical_failures',
        'submitted_at',
        'pdf_generated_at',
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
        'activity_id' => 'integer',
        'score' => 'decimal:2',
        'critical_failures' => 'integer',
        'submitted_at' => 'immutable_datetime',
        'pdf_generated_at' => 'immutable_datetime',
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

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
