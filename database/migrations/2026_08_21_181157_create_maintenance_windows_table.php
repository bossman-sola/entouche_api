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
        Schema::create('maintenance_windows', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message')->nullable();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            $table->unsignedInteger('notify_before_minutes')->default(1440);

            $table->string('affected_service')->default('entire_system');

            $table->boolean('send_email_notifications')->default(true);
            $table->boolean('show_maintenance_page')->default(true);

            $table->enum('status', [
                'scheduled',
                'in_progress',
                'completed',
                'cancelled',
            ])->default('scheduled');

            $table->foreignId('created_by')->constrained('users');

            $table->dateTime('notification_sent_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_windows');
    }
};
