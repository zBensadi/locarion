<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create permissions (from 03-authorization-and-roles.md)
        $permissions = [
            'fleet.view', 'fleet.create', 'fleet.update', 'fleet.delete', 'fleet.images.manage',
            'reservations.view', 'reservations.create', 'reservations.update', 'reservations.status.update',
            'customers.view', 'customers.create', 'customers.update', 'customers.delete',
            'contracts.view', 'contracts.generate',
            'billing.invoices.view', 'billing.invoices.manage', 'billing.payments.record',
            'reports.view',
            'agency.settings.view', 'agency.settings.update',
            'platform.agencies.manage', 'platform.agencies.impersonate', 'platform.regions.manage',
            'platform.categories.manage', 'platform.settings.manage', 'platform.stats.view',
            'employees.view', 'employees.create', 'employees.update', 'employees.permissions.manage', 'employees.deactivate',
            'booking-requests.view', 'booking-requests.approve', 'booking-requests.reject',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // 2. Create roles and assign permissions
        $superAdmin = Role::create(['name' => 'super-admin']);
        $superAdmin->givePermissionTo([
            'platform.agencies.manage', 'platform.agencies.impersonate', 'platform.regions.manage',
            'platform.categories.manage', 'platform.settings.manage', 'platform.stats.view',
        ]);

        $agencyAdmin = Role::create(['name' => 'agency-admin']);
        $agencyAdmin->givePermissionTo([
            'fleet.view', 'fleet.create', 'fleet.update', 'fleet.delete', 'fleet.images.manage',
            'reservations.view', 'reservations.create', 'reservations.update', 'reservations.status.update',
            'customers.view', 'customers.create', 'customers.update', 'customers.delete',
            'contracts.view', 'contracts.generate',
            'billing.invoices.view', 'billing.invoices.manage', 'billing.payments.record',
            'reports.view',
            'agency.settings.view', 'agency.settings.update',
            'employees.view', 'employees.create', 'employees.update', 'employees.permissions.manage', 'employees.deactivate',
            'booking-requests.view', 'booking-requests.approve', 'booking-requests.reject',
        ]);

        // Employee gets no default permissions (as per spec)
        Role::create(['name' => 'employee']);
    }
}
