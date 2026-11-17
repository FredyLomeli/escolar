<?php

namespace App\Policies;

use App\Models\AcademicPeriod;
use App\Models\User;

class AcademicPeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCoordinator();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isCoordinator();
    }

    public function update(User $user, AcademicPeriod $academicPeriod): bool
    {
        return $user->isAdmin() || $user->isCoordinator();
    }
}
