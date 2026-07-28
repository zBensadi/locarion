<?php

namespace App\Domain\PlatformAdmin\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Tenancy\Models\Agency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateAgencyAction
{
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $agency = Agency::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'status' => $data['status'],
            ]);

            $temporaryPassword = Str::random(12);

            $adminUser = User::create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($temporaryPassword),
                'agency_id' => $agency->id,
                'is_active' => true,
            ]);

            // Assign the agency-admin role within the agency's team context
            setPermissionsTeamId($agency->id);
            $adminUser->assignRole('agency-admin');
            
            // Revert back to the global team context (or previous one) if needed
            // For now, since this is called in an API request that resolves and dies, it's fine.
            // But to be safe:
            setPermissionsTeamId(tenant()->id ?? '00000000-0000-0000-0000-000000000000');

            return [
                'agency' => $agency,
                'admin' => [
                    'email' => $adminUser->email,
                    'temporary_password' => $temporaryPassword,
                ],
            ];
        });
    }
}
