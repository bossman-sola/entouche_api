<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->decimal('quantity_on_hand', 15, 3)->default(0);
            $table->decimal('quantity_reserved', 15, 3)->default(0);
            $table->decimal('quantity_available', 15, 3)->default(0);
            $table->timestamp('last_transaction_at')->nullable();
            $table->timestamps();
            $table->unique(['item_id', 'warehouse_id', 'warehouse_location_id'], 'stock_balance_unique_location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
    }
};
