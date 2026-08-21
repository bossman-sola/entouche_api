<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('warehouse_locations', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('type');
            $table->unsignedInteger('capacity')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_locations', function (Blueprint $table): void {
            $table->dropColumn(['description', 'capacity']);
        });
    }
};