<?php

declare(strict_types=1);

namespace App\Domain\OpsFortress\Jurisdictions\Models;

use App\Domain\OpsFortress\Lookups\Models\Country;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JurisdictionRegulatoryProfile extends Model
{
    use SoftDeletes, UsesUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'legislation_references' => 'array',
            'codes_of_practice' => 'array',
            'last_reviewed_at' => 'date',
            'active_status' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
