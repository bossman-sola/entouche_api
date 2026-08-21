<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('warehouse_locations', function (Blueprint $table) {
            if (! Schema::hasColumn('warehouse_locations', 'description')) {
                $table->text('description')->nullable();
            }

            if (! Schema::hasColumn('warehouse_locations', 'capacity')) {
                $table->decimal('capacity', 15, 3)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouse_locations', function (Blueprint $table) {
            if (Schema::hasColumn('warehouse_locations', 'description')) {
                $table->dropColumn('description');
            }

            if (Schema::hasColumn('warehouse_locations', 'capacity')) {
                $table->dropColumn('capacity');
            }
        });
    }
};
