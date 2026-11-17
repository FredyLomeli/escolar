<?php

namespace App\Policies;

use App\Models\Shift;
use App\Models\User;

class ShiftPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCoordinator();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isCoordinator();
    }

    public function update(User $user, Shift $shift): bool
    {
        return $user->isAdmin() || $user->isCoordinator();
    }
}
