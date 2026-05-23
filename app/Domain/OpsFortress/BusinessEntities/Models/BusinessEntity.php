<?php

declare(strict_types=1);

namespace App\Domain\OpsFortress\BusinessEntities\Models;

use App\Domain\OpsFortress\Lookups\Models\Country;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;

class BusinessEntity extends Model
{
    use SoftDeletes, UsesUuidPrimaryKey;

    protected $guarded = [];

    protected $hidden = [
        'blockchain_id',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $businessEntity): void {
            $original = $businessEntity->getOriginal('blockchain_id');

            if ($original !== null && $businessEntity->isDirty('blockchain_id')) {
                throw new RuntimeException('BusinessEntity.blockchain_id is immutable once set.');
            }
        });
    }

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
