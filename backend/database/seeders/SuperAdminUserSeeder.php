<?php

namespace Database\Seeders;

use App\Domain\Identity\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@locarion.com',
            'password' => Hash::make('password'),
            'agency_id' => null, // Super admin has no specific agency
            'is_active' => true,
        ]);

        // Explicitly set team context to a dummy UUID for global super-admin assignment
        // Since team_id is part of the composite primary key, it cannot be null.
        setPermissionsTeamId('00000000-0000-0000-0000-000000000000');
        $superAdmin->assignRole('super-admin');
    }
}
