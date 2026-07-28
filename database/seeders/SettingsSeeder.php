<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['group' => 'general', 'key' => 'general.company_name', 'value' => 'InventoryPro', 'type' => 'string'],
            ['group' => 'general', 'key' => 'general.currency', 'value' => 'NGN', 'type' => 'string'],
            ['group' => 'numbering', 'key' => 'numbering.item_prefix', 'value' => 'ITM', 'type' => 'string'],
            ['group' => 'numbering', 'key' => 'numbering.receipt_prefix', 'value' => 'RCPT', 'type' => 'string'],
            ['group' => 'numbering', 'key' => 'numbering.transfer_prefix', 'value' => 'TRF', 'type' => 'string'],
            ['group' => 'numbering', 'key' => 'numbering.adjustment_prefix', 'value' => 'ADJ', 'type' => 'string'],
            ['group' => 'numbering', 'key' => 'numbering.stock_count_prefix', 'value' => 'SC', 'type' => 'string'],
            ['group' => 'numbering', 'key' => 'numbering.transaction_prefix', 'value' => 'TXN', 'type' => 'string'],
            ['group' => 'inventory', 'key' => 'inventory.low_stock_alerts', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'inventory', 'key' => 'inventory.allow_negative_stock', 'value' => '0', 'type' => 'boolean'],
            ['group' => 'system', 'key' => 'system.maintenance_mode', 'value' => '0', 'type' => 'boolean'],
            ['group' => 'system', 'key' => 'system.maintenance_message', 'value' => '', 'type' => 'string'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
