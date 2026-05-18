<?php

declare(strict_types=1);

namespace App\Domain\Whs\Evidence\Models;

use App\Domain\Shared\Context\BelongsToAccount;
use App\Domain\Whs\Runtime\Models\PrestartSubmission;
use App\Domain\Whs\Runtime\Models\WorkerTaskSession;
use App\Models\Concerns\UsesUuidPrimaryKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvidenceFile extends Model
{
    use BelongsToAccount, UsesUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function workerTaskSession(): BelongsTo
    {
        return $this->belongsTo(WorkerTaskSession::class);
    }

    public function prestartSubmission(): BelongsTo
    {
        return $this->belongsTo(PrestartSubmission::class);
    }

    public function signature(): BelongsTo
    {
        return $this->belongsTo(Signature::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
