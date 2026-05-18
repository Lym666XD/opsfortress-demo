<?php

declare(strict_types=1);

namespace App\Domain\Whs\Runtime\Models;

use App\Domain\OpsFortress\BusinessEntities\Models\BusinessEntity;
use App\Domain\OpsFortress\Workplaces\Models\Workplace;
use App\Domain\Shared\Context\BelongsToAccount;
use App\Domain\Whs\Swms\Models\SwmsVersion;
use App\Domain\Whs\Tasks\Models\Task;
use App\Models\Concerns\UsesUuidPrimaryKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkerTaskSession extends Model
{
    use BelongsToAccount, UsesUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
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

    public function swmsVersion(): BelongsTo
    {
        return $this->belongsTo(SwmsVersion::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_user_id');
    }

    public function swmsStepEvents(): HasMany
    {
        return $this->hasMany(SwmsStepEvent::class);
    }

    public function prestartSubmissions(): HasMany
    {
        return $this->hasMany(PrestartSubmission::class);
    }
}
