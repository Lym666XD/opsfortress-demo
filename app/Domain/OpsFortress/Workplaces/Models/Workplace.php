<?php

declare(strict_types=1);

namespace App\Domain\OpsFortress\Workplaces\Models;

use App\Domain\OpsFortress\Businesses\Models\Business;
use App\Domain\Shared\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Workplace extends Model
{
    use BelongsToTenant;
    use HasUuids;

    protected $table = 'workplaces';

    protected $fillable = [
        'tenant_id',
        'business_id',
        'uuid',
        'name',
        'code',
        'classification',
        'street_address',
        'suburb',
        'city',
        'state',
        'postcode',
        'country',
        'latitude',
        'longitude',
        'geofence_radius_meters',
        'active',
        'metadata',
    ];

    protected $casts = [
        // Foreign keys — explicit int casts because PG's PDO driver returns
        // BIGINT columns as strings by default. Without these, type-hinted
        // ?int parameters (e.g. AuditService::record) fail.
        'tenant_id' => 'integer',
        'business_id' => 'integer',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'geofence_radius_meters' => 'integer',
        'active' => 'boolean',
        'metadata' => 'array',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
