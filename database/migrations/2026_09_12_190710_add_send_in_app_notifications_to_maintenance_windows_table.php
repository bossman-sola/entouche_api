<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'maintenance_windows',
            function (Blueprint $table) {
                $table->boolean(
                    'send_in_app_notifications'
                )
                    ->default(true)
                    ->after('affected_service');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'maintenance_windows',
            function (Blueprint $table) {
                $table->dropColumn(
                    'send_in_app_notifications'
                );
            }
        );
    }
};
