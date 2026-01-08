<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charger_stations', function (Blueprint $table) {
            if (!Schema::hasColumn('charger_stations', 'charger_number')) {
                $table->integer('charger_number')->default(0)->after('location');
            }
        });
    }

    public function down(): void
    {
        Schema::table('charger_stations', function (Blueprint $table) {
            if (Schema::hasColumn('charger_stations', 'charger_number')) {
                $table->dropColumn('charger_number');
            }
        });
    }
};
