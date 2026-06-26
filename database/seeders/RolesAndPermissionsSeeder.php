<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'items.view', 'items.create', 'items.edit', 'items.delete',
            'receipts.view', 'receipts.create', 'receipts.approve',
            'transfers.view', 'transfers.create', 'transfers.approve',
            'adjustments.view', 'adjustments.create', 'adjustments.approve',
            'stock_counts.view', 'stock_counts.create', 'stock_counts.approve',
            'reports.view', 'data_import.manage', 'audit_logs.view',
            'users.manage', 'settings.manage', 'master-data.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        $roles = [
            'system_administrator' => $permissions,
            'warehouse_manager' => [
                'items.view', 'items.create', 'items.edit',
                'receipts.view', 'receipts.create', 'receipts.approve',
                'transfers.view', 'transfers.create', 'transfers.approve',
                'adjustments.view', 'adjustments.create', 'adjustments.approve',
                'stock_counts.view', 'stock_counts.create', 'stock_counts.approve',
                'reports.view', 'data_import.manage', 'master-data.manage',
            ],
            'inventory_officer' => [
                'items.view', 'items.create', 'items.edit',
                'receipts.view', 'receipts.create',
                'transfers.view', 'transfers.create',
                'adjustments.view', 'adjustments.create',
                'stock_counts.view', 'stock_counts.create',
                'reports.view', 'master-data.manage',
            ],
            'management_viewer' => [
                'items.view', 'receipts.view', 'transfers.view',
                'adjustments.view', 'stock_counts.view', 'reports.view',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'api'])
                ->syncPermissions($rolePermissions);
        }
    }
}
