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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();

            $table->integer('daily_limit_hours')->default(2);


            // وقت الحجز
            $table->time('opening_time')->default('07:00');
            $table->time('closing_time')->default('22:00');

            // وضع الصيانة
            $table->boolean('maintenance_mode')->default(false);

            // عدد التحذيرات
            $table->integer('max_warnings')->default(3);

            // سماحية بعد انتهاء الجلسة
            $table->integer('grace_period_minutes')->default(10);

            // ملاحظات
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::dropIfExists('settings');
    }
};
