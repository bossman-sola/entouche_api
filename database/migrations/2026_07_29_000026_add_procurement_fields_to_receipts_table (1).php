<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table): void {

            $table->string('invoice_number')->nullable()->after('receipt_number');

            $table->string('vendor_po_number')->nullable()->after('invoice_number');

            $table->string('api_po_number')->nullable()->after('vendor_po_number');

            $table->date('payment_date')->nullable()->after('receipt_date');
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table): void {
            $table->dropColumn(['invoice_number', 'vendor_po_number', 'api_po_number', 'payment_date']);
        });
    }
};
