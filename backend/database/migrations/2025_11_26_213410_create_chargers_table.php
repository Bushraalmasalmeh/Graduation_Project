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
        Schema::create('chargers', function (Blueprint $table) {
            $table->id();
            // كل شاحن تابع لكابينة معيّنة
            $table->foreignId('cabinet_id')
                ->constrained('cabinets')
                ->onDelete('cascade');

            // مثال: Charger 1, Charger 2 
            // (لأن نفس الكابينة ممكن يكون فيها 2)
            $table->string('charger_number');
            $table->string('uid')->unique();


            // حالة الشاحن حسب التصميم: Available / Busy / Offline / Maintenance
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
        Schema::dropIfExists('chargers');
    }
};
