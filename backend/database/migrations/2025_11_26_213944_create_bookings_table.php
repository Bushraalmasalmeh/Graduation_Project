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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('station_id')
                ->constrained('charger_stations')
                ->onDelete('cascade');

            $table->foreignId('cabinet_id')
                ->constrained('cabinets')
                ->onDelete('cascade');

            $table->foreignId('charger_id')
                ->constrained('chargers')
                ->onDelete('cascade');

            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();

            $table->enum('status', [
                'pending',
                'active',
                'completed',
                'cancelled'
            ])->default('pending');

            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
