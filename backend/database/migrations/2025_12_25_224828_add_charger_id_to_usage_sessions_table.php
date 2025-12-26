<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('usage_sessions', function (Blueprint $table) {
            // Add charger_id as nullable first (safe)
            $table->foreignId('charger_id')
                ->nullable()
                ->after('user_id')
                ->constrained('chargers')
                ->nullOnDelete();

            // If you want to make it required later, you can:
            // 1. First add nullable column
            // 2. Backfill data
            // 3. Then make it not nullable in another migration
        });
    }

    public function down()
    {
        Schema::table('usage_sessions', function (Blueprint $table) {
            $table->dropForeign(['charger_id']);
            $table->dropColumn('charger_id');
        });
    }
};
