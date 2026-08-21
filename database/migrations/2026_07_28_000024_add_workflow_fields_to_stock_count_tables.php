<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_counts', function (Blueprint $table): void {

            $table->string('count_type')->nullable()->after('warehouse_location_id');
            $table->string('priority')->default('medium')->after('count_type');
            $table->time('start_time')->nullable()->after('count_date');

            $table->foreignId('submitted_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('started_at');

            $table->foreignId('rejected_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('approved_at');

            $table->foreignId('recount_requested_by')->nullable()->after('rejected_by')->constrained('users')->nullOnDelete();
            $table->timestamp('recount_requested_at')->nullable()->after('rejected_at');
        });

        Schema::table('stock_count_items', function (Blueprint $table): void {

            $table->timestamp('counted_at')->nullable()->after('counted_quantity');
            $table->foreignId('counted_by')->nullable()->after('counted_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_count_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('counted_by');
            $table->dropColumn('counted_at');
        });

        Schema::table('stock_counts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('recount_requested_by');
            $table->dropColumn('recount_requested_at');
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn('rejected_at');
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropColumn('submitted_at');
            $table->dropColumn(['count_type', 'priority', 'start_time']);
        });
    }
};
