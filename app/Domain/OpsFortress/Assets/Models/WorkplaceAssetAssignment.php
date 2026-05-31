<?php

declare(strict_types=1);

namespace App\Domain\OpsFortress\Assets\Models;

use App\Domain\OpsFortress\Workplaces\Models\Workplace;
use App\Domain\Shared\Context\BelongsToAccount;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkplaceAssetAssignment extends Model
{
    use BelongsToAccount, SoftDeletes, UsesUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'assigned_from' => 'date',
            'assigned_to' => 'date',
            'is_primary' => 'boolean',
            'active_status' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function workplace(): BelongsTo
    {
        return $this->belongsTo(Workplace::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
