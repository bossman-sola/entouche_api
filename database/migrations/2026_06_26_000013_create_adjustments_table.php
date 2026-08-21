<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adjustments', function (Blueprint $table): void {
            $table->id();
            $table->string('adjustment_number')->unique();
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->string('adjustment_type');
            $table->string('reason')->nullable();
            $table->foreignId('adjusted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('adjustment_date')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adjustments');
    }
};
