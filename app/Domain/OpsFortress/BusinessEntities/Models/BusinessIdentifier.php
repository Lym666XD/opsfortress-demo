<?php

declare(strict_types=1);

namespace App\Domain\OpsFortress\BusinessEntities\Models;

use App\Domain\OpsFortress\Lookups\Models\BusinessIdentifierType;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessIdentifier extends Model
{
    use SoftDeletes, UsesUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
            'verified_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function businessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function identifierType(): BelongsTo
    {
        return $this->belongsTo(BusinessIdentifierType::class);
    }
}
