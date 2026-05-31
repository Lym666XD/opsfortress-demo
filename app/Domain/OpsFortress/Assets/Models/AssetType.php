<?php

declare(strict_types=1);

namespace App\Domain\OpsFortress\Assets\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetType extends Model
{
    use SoftDeletes, UsesUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'inspection_required' => 'boolean',
            'prestart_required' => 'boolean',
            'posttask_required' => 'boolean',
            'maintenance_trigger_required' => 'boolean',
            'evidence_required' => 'boolean',
            'active_status' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
