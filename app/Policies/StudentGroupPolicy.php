<?php

namespace App\Policies;

use App\Models\StudentGroup;
use App\Models\User;

class StudentGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCoordinator();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isCoordinator();
    }

    public function update(User $user, StudentGroup $studentGroup): bool
    {
        return $user->isAdmin() || $user->isCoordinator();
    }
}
