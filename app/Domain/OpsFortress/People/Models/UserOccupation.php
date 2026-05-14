<?php

declare(strict_types=1);

namespace App\Domain\OpsFortress\People\Models;

use App\Domain\OpsFortress\Occupations\Models\Occupation;
use App\Domain\Shared\Tenancy\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserOccupation extends Model
{
    use BelongsToTenant;

    protected $table = 'user_occupations';

    protected $fillable = [
        'tenant_id',
        'business_id',
        'user_id',
        'occupation_id',
        'is_primary',
    ];

    protected $casts = [
        // FK int casts — PG's PDO driver returns BIGINT as string.
        'tenant_id' => 'integer',
        'business_id' => 'integer',
        'user_id' => 'integer',
        'occupation_id' => 'integer',
        'is_primary' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function occupation(): BelongsTo
    {
        return $this->belongsTo(Occupation::class);
    }
}
