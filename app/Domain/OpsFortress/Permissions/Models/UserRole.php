<?php

declare(strict_types=1);

namespace App\Domain\OpsFortress\Permissions\Models;

use App\Domain\Shared\Tenancy\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRole extends Model
{
    use BelongsToTenant;

    protected $table = 'user_roles';

    protected $fillable = [
        'tenant_id',
        'business_id',
        'user_id',
        'role_id',
        'assigned_by',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        // FK int casts — PG's PDO driver returns BIGINT as string.
        'tenant_id' => 'integer',
        'business_id' => 'integer',
        'user_id' => 'integer',
        'role_id' => 'integer',
        'assigned_by' => 'integer',
        'starts_at' => 'immutable_datetime',
        'ends_at' => 'immutable_datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
