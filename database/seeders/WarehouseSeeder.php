<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::firstOrCreate(
            ['code' => 'MAIN'],
            ['name' => 'Main Warehouse', 'address' => 'Head Office', 'status' => 'active']
        );

        Location::firstOrCreate(
            ['warehouse_id' => $warehouse->id, 'code' => 'A1'],
            ['name' => 'Storage Area A1', 'type' => 'storage', 'status' => 'active']
        );
    }
}
