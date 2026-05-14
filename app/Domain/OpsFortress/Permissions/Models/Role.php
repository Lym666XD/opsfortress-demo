<?php

declare(strict_types=1);

namespace App\Domain\OpsFortress\Permissions\Models;

use App\Domain\OpsFortress\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Permission role (Worker / Supervisor / Manager / Admin).
 *
 * Tenant-scoped via nullable tenant_id:
 *   - tenant_id NULL  → platform-level role (e.g. global Admin, Worker template)
 *   - tenant_id !NULL → tenant-specific custom role
 *
 * NOT using BelongsToTenant trait because the nullable tenant_id needs
 * to allow platform-level rows the global scope would hide.
 *
 * This model is also the table that spatie/laravel-permission is configured
 * against (see config/permission.php after install).
 */
class Role extends Model
{
    protected $table = 'roles';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'scope',
        'description',
    ];

    /**
     * FK int casts — PG's PDO driver returns BIGINT as string.
     */
    protected $casts = [
        'tenant_id' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
