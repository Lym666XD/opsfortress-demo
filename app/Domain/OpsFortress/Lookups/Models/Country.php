<?php

declare(strict_types=1);

namespace App\Domain\OpsFortress\Lookups\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use UsesUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function businessIdentifierTypes(): HasMany
    {
        return $this->hasMany(BusinessIdentifierType::class);
    }
}
