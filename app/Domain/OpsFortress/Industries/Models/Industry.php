<?php

declare(strict_types=1);

namespace App\Domain\OpsFortress\Industries\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Platform-level catalog. NOT tenant-scoped — every tenant draws from
 * the same industry taxonomy (per OpsFortress Central Source Pack §3.21).
 */
class Industry extends Model
{
    protected $table = 'industries';

    protected $fillable = [
        'code',
        'parent_id',
        'name',
        'level',
        'active',
    ];

    protected $casts = [
        // FK int cast — PG's PDO driver returns BIGINT as string.
        'parent_id' => 'integer',
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
}
