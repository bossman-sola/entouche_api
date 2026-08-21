<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_of_measure_id')->constrained('units_of_measure');
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->string('name');
            $table->string('item_type')->default('stock_item');
            $table->string('brand')->nullable();
            $table->text('description')->nullable();
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('reorder_level', 15, 3)->default(0);
            $table->string('image_path')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['name', 'sku', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
