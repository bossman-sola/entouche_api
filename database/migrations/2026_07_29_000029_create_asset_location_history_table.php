<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_location_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->timestamp('moved_at')->useCurrent();
            $table->foreignId('moved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableMorphs('reference');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_location_history');
    }
};
