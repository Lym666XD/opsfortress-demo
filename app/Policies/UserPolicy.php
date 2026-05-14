<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Audit hardening A3 — defense in depth around the User model.
 *
 * User is intentionally NOT using the BelongsToTenant global scope (see User
 * model docblock: login bootstrap requires unscoped reads). The risk is that
 * future controllers may query users across tenants by accident. This policy
 * is the chokepoint:
 *
 *   - Every UserController action MUST call $this->authorize() against this
 *     policy before returning user data.
 *   - The policy enforces "actor and target must share the same tenant_id".
 *   - viewAny is gated by role (admin or manager) — listing users at all is
 *     an admin function.
 *
 * NOT a substitute for tenant_id checks in queries — controllers must still
 * filter $query->where('tenant_id', $actor->tenant_id) before paginating.
 * This policy only protects single-record operations.
 *
 * Slice 3 (Invite Worker) will be the first consumer of this policy.
 */
final class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasRole('admin') || $actor->hasRole('manager');
    }

    public function view(User $actor, User $target): bool
    {
        return $this->sameTenant($actor, $target)
            && ($actor->id === $target->id
                || $actor->hasRole('admin')
                || $actor->hasRole('manager'));
    }

    public function create(User $actor): bool
    {
        return $actor->hasRole('admin');
    }

    public function update(User $actor, User $target): bool
    {
        if (! $this->sameTenant($actor, $target)) {
            return false;
        }

        // Self-edit is allowed (profile updates).
        if ($actor->id === $target->id) {
            return true;
        }

        return $actor->hasRole('admin');
    }

    public function delete(User $actor, User $target): bool
    {
        // No one deletes themselves. Admins delete others within their tenant.
        // (Soft delete / suspension is the preferred path — slice 3+ decision.)
        return $this->sameTenant($actor, $target)
            && $actor->id !== $target->id
            && $actor->hasRole('admin');
    }

    /**
     * Hard tenant boundary. Two users with different tenant_id can never
     * see/act on each other through controllers that honor this policy.
     */
    private function sameTenant(User $actor, User $target): bool
    {
        return $actor->tenant_id !== null
            && $actor->tenant_id === $target->tenant_id;
    }
}
