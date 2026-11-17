<?php

namespace App\Policies;

use App\Models\GroupTerm;
use App\Models\User;

class GroupTermPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCoordinator();
    }

    public function update(User $user, GroupTerm $groupTerm): bool
    {
        return $user->isAdmin() || $user->isCoordinator();
    }
}
