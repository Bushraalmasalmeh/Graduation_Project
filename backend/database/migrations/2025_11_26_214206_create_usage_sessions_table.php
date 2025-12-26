<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_sessions', function (Blueprint $table) {
            $table->id();

            // USER
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // BOOKING
            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->onDelete('cascade');

            // SESSION DATA
            $table->timestamp('session_start');
            $table->timestamp('session_end')->nullable();
            $table->integer('duration')->nullable();

            // STATUS
            $table->enum('status', ['active', 'completed', 'terminated'])
                ->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_sessions');
    }
};
