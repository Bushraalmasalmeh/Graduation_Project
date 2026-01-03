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
        Schema::create('charger_stations', function (Blueprint $table) {
            $table->id();
            $table->string('station_code');

            $table->string('station_name'); // IT Station, Arch Station, etc.
            $table->string('location');     // IT Building, Business Building, etc.

            $table->integer('total_cabinets')->default(1);

            $table->enum('status', ['active', 'offline', 'maintenance'])->default('active');

            $table->text('description')->nullable();
            $table->integer('charger_number')->nullable(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charger_stations');
    }
};
