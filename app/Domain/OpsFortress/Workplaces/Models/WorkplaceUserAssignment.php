<?php

declare(strict_types=1);

namespace App\Domain\OpsFortress\Workplaces\Models;

use App\Domain\Shared\Tenancy\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cross-business contractor scenario:
 *
 *   - $assignment->business_id     = the HOST business (the workplace's owning business)
 *   - $assignment->user->business_id = the user's HOME business (their employer)
 *
 * For employees these are the same. For contractors (e.g. ABC Cleaning sending
 * a worker to an XYZ Construction site) they differ:
 *   - user.business_id     = ABC Cleaning   (their employer)
 *   - assignment.business_id = XYZ Construction (the host)
 *
 * No separate host_business_id column is needed.
 */
class WorkplaceUserAssignment extends Model
{
    use BelongsToTenant;

    protected $table = 'workplace_user_assignments';

    protected $fillable = [
        'tenant_id',
        'business_id',
        'workplace_id',
        'user_id',
        'role_context',
        'active_from',
        'active_to',
    ];

    protected $casts = [
        // FK int casts — PG's PDO driver returns BIGINT as string.
        'tenant_id' => 'integer',
        'business_id' => 'integer',
        'workplace_id' => 'integer',
        'user_id' => 'integer',
        'active_from' => 'immutable_datetime',
        'active_to' => 'immutable_datetime',
    ];

    public function workplace(): BelongsTo
    {
        return $this->belongsTo(Workplace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
