<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_count_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_count_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained();
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->decimal('system_quantity', 15, 3)->default(0);
            $table->decimal('counted_quantity', 15, 3)->default(0);
            $table->decimal('variance_quantity', 15, 3)->storedAs('counted_quantity - system_quantity');
            $table->boolean('adjustment_created')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['stock_count_id', 'item_id', 'warehouse_location_id'], 'stock_count_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_count_items');
    }
};
