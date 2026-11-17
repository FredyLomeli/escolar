<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCoordinator();
    }

    public function update(User $user, Setting $setting): bool
    {
        return $user->isAdmin() || $user->isCoordinator();
    }
}
