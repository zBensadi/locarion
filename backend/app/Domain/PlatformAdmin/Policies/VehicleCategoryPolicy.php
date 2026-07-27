<?php

namespace App\Domain\PlatformAdmin\Policies;

use App\Domain\Identity\Models\User;
use App\Domain\PlatformAdmin\Models\VehicleCategory;

class VehicleCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('platform.categories.manage');
    }

    public function update(User $user, VehicleCategory $category): bool
    {
        return $user->hasPermissionTo('platform.categories.manage');
    }

    public function delete(User $user, VehicleCategory $category): bool
    {
        return $user->hasPermissionTo('platform.categories.manage');
    }
}
