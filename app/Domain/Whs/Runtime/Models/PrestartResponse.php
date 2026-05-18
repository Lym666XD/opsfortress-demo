<?php

declare(strict_types=1);

namespace App\Domain\Whs\Runtime\Models;

use App\Domain\Whs\Swms\Models\PrestartQuestion;
use App\Models\Concerns\UsesUuidPrimaryKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestartResponse extends Model
{
    use UsesUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'answer_boolean' => 'boolean',
            'answer_number' => 'decimal:4',
            'answer_json' => 'array',
            'is_critical_failure' => 'boolean',
            'score_awarded' => 'decimal:2',
            'answered_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function prestartSubmission(): BelongsTo
    {
        return $this->belongsTo(PrestartSubmission::class);
    }

    public function prestartQuestion(): BelongsTo
    {
        return $this->belongsTo(PrestartQuestion::class);
    }

    public function workerTaskSession(): BelongsTo
    {
        return $this->belongsTo(WorkerTaskSession::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_user_id');
    }
}
