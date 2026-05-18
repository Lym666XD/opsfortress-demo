<?php

declare(strict_types=1);

namespace App\Domain\Whs\Runtime\Models;

use App\Domain\OpsFortress\BusinessEntities\Models\BusinessEntity;
use App\Domain\OpsFortress\Workplaces\Models\Workplace;
use App\Domain\Shared\Context\BelongsToAccount;
use App\Domain\Whs\Tasks\Models\Task;
use App\Models\Concerns\UsesUuidPrimaryKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrestartSubmission extends Model
{
    use BelongsToAccount, UsesUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'score_percent' => 'decimal:2',
            'has_critical_failure' => 'boolean',
            'submitted_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function workerTaskSession(): BelongsTo
    {
        return $this->belongsTo(WorkerTaskSession::class);
    }

    public function businessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function workplace(): BelongsTo
    {
        return $this->belongsTo(Workplace::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_user_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(PrestartResponse::class);
    }
}
