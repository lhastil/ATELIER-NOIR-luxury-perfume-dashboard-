<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notifications an administrator opened one at a time from the bell. Together
 * with users.notifications_read_at ("everything up to here is read") this
 * decides which notifications are unread. "Mark all as read" clears the user's
 * rows, since the new notifications_read_at already covers them.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // The notification's source: order, customer or message, and that record's id.
            $table->string('type', 20);
            $table->unsignedBigInteger('record_id');
            $table->timestamps();

            $table->unique(['user_id', 'type', 'record_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_reads');
    }
};
