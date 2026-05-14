<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\OpsFortress\Workplaces\Models\Workplace;
use App\Models\User;

/**
 * Workplace authorization (M10 Slice 1).
 *
 * Coarse-grained role check via User::hasRole() — lightweight pre-spatie
 * helper. Tenant isolation is NOT enforced here because the BelongsToTenant
 * global scope on Workplace already ensures admin can only see/touch rows
 * in their own tenant. Policy is purely about role membership.
 *
 * View / update / delete are intentionally omitted from this slice — they
 * arrive in a follow-up slice once the create flow is validated.
 */
final class WorkplacePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('manager');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('manager');
    }
}
