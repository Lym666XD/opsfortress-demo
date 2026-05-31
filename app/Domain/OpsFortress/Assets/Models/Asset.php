<?php

declare(strict_types=1);

namespace App\Domain\OpsFortress\Assets\Models;

use App\Domain\OpsFortress\BusinessEntities\Models\BusinessEntity;
use App\Domain\Shared\Context\BelongsToAccount;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use BelongsToAccount, SoftDeletes, UsesUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'inspection_required' => 'boolean',
            'prestart_required' => 'boolean',
            'posttask_required' => 'boolean',
            'maintenance_trigger_required' => 'boolean',
            'evidence_required' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function businessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function assetType(): BelongsTo
    {
        return $this->belongsTo(AssetType::class);
    }

    public function workplaceAssignments(): HasMany
    {
        return $this->hasMany(WorkplaceAssetAssignment::class);
    }
}
