<?php

declare(strict_types=1);

namespace App\Domain\OpsFortress\Tenancy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Top-level tenant. Every other domain row hangs off a tenant_id.
 * The tenant table itself is NOT scoped (no BelongsToTenant) — it's the root.
 */
class Tenant extends Model
{
    protected $table = 'tenants';

    protected $fillable = [
        'uuid',
        'slug',
        'name',
        'status',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    /**
     * Tell HasUuids to use the 'uuid' column rather than the default 'id'.
     * The tenant has both an integer 'id' and a 'uuid' for external references.
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    use HasUuids;
}
