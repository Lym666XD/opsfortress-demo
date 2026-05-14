<?php

declare(strict_types=1);

namespace App\Domain\Whs\TaskPacks\Models;

use App\Domain\OpsFortress\Industries\Models\Industry;
use App\Domain\OpsFortress\Occupations\Models\Occupation;
use App\Domain\OpsFortress\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Catalog-layer model. Tenant-scoped via nullable tenant_id:
 *   - tenant_id NULL  → platform-shared task pack (e.g. Kevin's standard library)
 *   - tenant_id !NULL → tenant-customized task pack
 *
 * NOT using BelongsToTenant trait (nullable tenant_id semantics).
 */
class TaskPack extends Model
{
    protected $table = 'task_packs';

    protected $fillable = [
        'tenant_id',
        'business_id',
        'code',
        'title',
        'category',
        'status',
        'version',
        'summary',
        'requires_swms_ack',
        'requires_prestart',
        'requires_posttask',
        'requires_training',
        'pdf_template',
        'rules',
    ];

    protected $casts = [
        // FK int casts — PG's PDO driver returns BIGINT as string.
        'tenant_id' => 'integer',
        'business_id' => 'integer',
        'requires_swms_ack' => 'boolean',
        'requires_prestart' => 'boolean',
        'requires_posttask' => 'boolean',
        'requires_training' => 'boolean',
        'rules' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function occupations(): BelongsToMany
    {
        return $this->belongsToMany(
            Occupation::class,
            'task_pack_occupations',
        )->withPivot('access_level')->withTimestamps();
    }

    public function industries(): BelongsToMany
    {
        return $this->belongsToMany(
            Industry::class,
            'task_pack_industries',
        )->withPivot('access_level')->withTimestamps();
    }
}
