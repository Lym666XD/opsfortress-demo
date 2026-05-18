<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Defense-in-depth around the unscoped User model.
 * User queries must still filter by account_id before pagination.
 */
final class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasRole('admin') || $actor->hasRole('manager');
    }

    public function view(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return true;
        }

        return $this->sameAccount($actor, $target)
            && ($actor->hasRole('admin') || $actor->hasRole('manager'));
    }

    public function create(User $actor): bool
    {
        return $actor->hasRole('admin');
    }

    public function update(User $actor, User $target): bool
    {
        // Self-edit is allowed (profile updates).
        if ($actor->id === $target->id) {
            return true;
        }

        return $this->sameAccount($actor, $target) && $actor->hasRole('admin');
    }

    public function delete(User $actor, User $target): bool
    {
        // No one deletes themselves. Admins delete others within their account.
        return $this->sameAccount($actor, $target)
            && $actor->id !== $target->id
            && $actor->hasRole('admin');
    }

    private function sameAccount(User $actor, User $target): bool
    {
        return $actor->account_id !== null
            && $actor->account_id === $target->account_id;
    }
}
