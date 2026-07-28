<?php

namespace App\Domain\PlatformAdmin\Policies;

use App\Domain\Identity\Models\User;
use App\Domain\Tenancy\Models\Agency;

class AgencyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('platform.agencies.manage');
    }

    public function view(User $user, Agency $agency): bool
    {
        return $user->hasPermissionTo('platform.agencies.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('platform.agencies.manage');
    }

    public function update(User $user, Agency $agency): bool
    {
        return $user->hasPermissionTo('platform.agencies.manage');
    }

    public function delete(User $user, Agency $agency): bool
    {
        return $user->hasPermissionTo('platform.agencies.manage');
    }
}
