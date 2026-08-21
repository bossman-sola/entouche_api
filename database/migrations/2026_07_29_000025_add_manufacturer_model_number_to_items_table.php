<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->string('manufacturer')->nullable()->after('name');
            $table->string('model_number')->nullable()->after('manufacturer');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->dropColumn(['manufacturer', 'model_number']);
        });
    }
};
