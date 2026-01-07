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
        Schema::table('cars', function (Blueprint $table) {
            $table->string('car_model')->nullable(false)->change();
            $table->string('plate_number')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->string('car_model')->nullable()->change();
            $table->string('plate_number')->nullable()->change();
        });
    }
};
