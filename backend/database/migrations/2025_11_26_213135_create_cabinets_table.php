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
        Schema::create('cabinets', function (Blueprint $table) {
            $table->id();
            // كل كابينة تابعة لمحطة
            $table->foreignId('station_id')
                ->constrained('charger_stations')
                ->onDelete('cascade');

            // مثال: Cabinet 1, Cabinet 2
            $table->integer('cabinet_number')->nullable(false);

            // عدد الشواحن بداخل الكابينة (1 أو 2)
            $table->integer('total_chargers')->default(1);

            // حالة الكابينة
            $table->enum('status', ['available', 'busy', 'offline', 'maintenance'])
                ->default('available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cabinets');
    }
};
