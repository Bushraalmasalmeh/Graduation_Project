<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('cancelled_by')->nullable()->after('status');
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
        });
    }

    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['cancelled_by', 'cancelled_at']);
        });
    }
};
