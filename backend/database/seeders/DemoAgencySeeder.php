<?php

namespace Database\Seeders;

use App\Domain\Identity\Models\User;
use App\Domain\Tenancy\Models\Agency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoAgencySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create a demo agency
        $agency = Agency::create([
            'name' => 'Demo Agency',
            'slug' => 'demo-agency-' . Str::random(5),
            'status' => 'active',
        ]);

        // 2. Create the Agency Admin
        $agencyAdmin = User::create([
            'name' => 'Agency Admin',
            'email' => 'admin@demo-agency.com',
            'password' => Hash::make('password'),
            'agency_id' => $agency->id,
            'is_active' => true,
        ]);

        // Assign the agency-admin role within the agency's team context
        setPermissionsTeamId($agency->id);
        $agencyAdmin->assignRole('agency-admin');

        // 3. Create an Employee
        $employee = User::create([
            'name' => 'Demo Employee',
            'email' => 'employee@demo-agency.com',
            'password' => Hash::make('password'),
            'agency_id' => $agency->id,
            'is_active' => true,
        ]);

        $employee->assignRole('employee');

        // Optionally give the employee some specific permissions
        $employee->givePermissionTo(['fleet.view', 'reservations.view']);
    }
}
