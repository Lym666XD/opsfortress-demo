<?php

declare(strict_types=1);

namespace App\Domain\Whs\Runtime\Models;

use App\Domain\Whs\Swms\Models\SwmsActivityStep;
use App\Models\Concerns\UsesUuidPrimaryKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SwmsStepEvent extends Model
{
    use UsesUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'read_started_at' => 'datetime',
            'read_completed_at' => 'datetime',
            'met_minimum_read_time' => 'boolean',
            'device_metadata' => 'array',
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function workerTaskSession(): BelongsTo
    {
        return $this->belongsTo(WorkerTaskSession::class);
    }

    public function swmsActivityStep(): BelongsTo
    {
        return $this->belongsTo(SwmsActivityStep::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_user_id');
    }
}
