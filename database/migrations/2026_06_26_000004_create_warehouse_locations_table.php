<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('type')->default('storage');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['warehouse_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_locations');
    }
};
