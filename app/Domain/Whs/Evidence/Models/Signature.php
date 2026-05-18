<?php

declare(strict_types=1);

namespace App\Domain\Whs\Evidence\Models;

use App\Domain\Shared\Context\BelongsToAccount;
use App\Domain\Whs\Runtime\Models\WorkerTaskSession;
use App\Models\Concerns\UsesUuidPrimaryKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signature extends Model
{
    use BelongsToAccount, UsesUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'signature_data' => 'array',
            'signed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function workerTaskSession(): BelongsTo
    {
        return $this->belongsTo(WorkerTaskSession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
