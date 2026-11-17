<?php

namespace App\Policies;

use App\Models\Curriculum;
use App\Models\User;

class CurriculumPolicy
{
    public function view(User $user, Curriculum $curriculum): bool
    {
        return $user->isAdmin() || $user->isCoordinator();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isCoordinator();
    }

    public function update(User $user, Curriculum $curriculum): bool
    {
        return $user->isAdmin() || $user->isCoordinator();
    }
}
