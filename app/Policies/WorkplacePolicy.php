<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

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
