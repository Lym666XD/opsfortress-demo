<?php

declare(strict_types=1);

namespace App\Domain\OpsFortress\BusinessEntities\Models;

use App\Domain\OpsFortress\Lookups\Models\Country;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessEntity extends Model
{
    use SoftDeletes, UsesUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'registered_address' => 'array',
            'postal_address' => 'array',
            'metadata' => 'array',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function identifiers(): HasMany
    {
        return $this->hasMany(BusinessIdentifier::class);
    }
}
