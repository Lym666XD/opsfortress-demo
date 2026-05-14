<?php

declare(strict_types=1);

namespace App\Domain\OpsFortress\Occupations\Models;

use App\Domain\OpsFortress\Industries\Models\Industry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Platform-level catalog. NOT tenant-scoped — same reasoning as Industry.
 * Per architecture record §4.6: "no occupation = no SWMS/SOP access".
 */
class Occupation extends Model
{
    protected $table = 'occupations';

    protected $fillable = [
        'code',
        'parent_id',
        'industry_id',
        'name',
        'level',
        'active',
    ];

    protected $casts = [
        // FK int casts — PG's PDO driver returns BIGINT as string.
        'parent_id' => 'integer',
        'industry_id' => 'integer',
        'level' => 'integer',
        'active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }
}
