<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charger_stations', function (Blueprint $table) {
            // إضافة عمود الـ department بعد عمود الـ location
            if (!Schema::hasColumn('charger_stations', 'department')) {
                $table->string('department')->after('location')->nullable();
            }

            // تعديل charger_number ليكون له قيمة افتراضية لتجنب خطأ 1364
            // في لارافيل 11 لا نحتاج لتنصيب حزم إضافية لتعديل الأعمدة
            $table->integer('charger_number')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('charger_stations', function (Blueprint $table) {
            if (Schema::hasColumn('charger_stations', 'department')) {
                $table->dropColumn('department');
            }
            $table->integer('charger_number')->change();
        });
    }
};
