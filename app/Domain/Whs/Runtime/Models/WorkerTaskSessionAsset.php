<?php

declare(strict_types=1);

namespace App\Domain\Whs\Runtime\Models;

use App\Domain\OpsFortress\Assets\Models\Asset;
use App\Models\Concerns\UsesUuidPrimaryKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerTaskSessionAsset extends Model
{
    use UsesUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'selected_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function workerTaskSession(): BelongsTo
    {
        return $this->belongsTo(WorkerTaskSession::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function selectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selected_by_user_id');
    }
}
