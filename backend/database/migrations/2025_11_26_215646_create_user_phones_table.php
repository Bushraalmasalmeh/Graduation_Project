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
        Schema::create('user_phones', function (Blueprint $table) {
            $table->id();

            // الرقم تابع لأي مستخدم؟
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // نوع الرقم: أساسي أو إضافي
            $table->enum('type', ['primary', 'secondary'])
                ->default('secondary');

            // رقم الهاتف
            $table->string('phone_number');

            // هل الرقم مفعل / تم توثيقه؟
            $table->boolean('is_verified')->default(false);


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_phones');
    }
};
