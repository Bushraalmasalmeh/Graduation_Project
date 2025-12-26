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
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();

            // من الشخص اللي أرسل الرسالة
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            // اسم الشخص (لو مش عامل تسجيل دخول)
            $table->string('name')->nullable();

            // ايميل الشخص (لو مش عامل تسجيل دخول)
            $table->string('email')->nullable();
            $table->string('phone');


            // محتوى الرسالة
            $table->text('message');

            // حالة الرسالة: تمت قراءتها ولا لسة؟
            $table->enum('status', ['pending', 'read', 'replied'])
                ->default('pending');

            // رد الإدارة على الرسالة (اختياري)
            $table->text('admin_reply')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
