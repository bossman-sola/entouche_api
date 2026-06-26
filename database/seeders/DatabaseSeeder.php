<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SettingsSeeder::class,
            WarehouseSeeder::class,
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@inventory.local'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('Admin@1234'),
                'status' => 'active',
            ]
        );

        $admin->assignRole('system_administrator');
    }
}
