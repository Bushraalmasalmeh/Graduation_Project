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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');

            $table->string('job_number')->unique();
            $table->string('device_token')->nullable();
            $table->string('department'); // IT, Business, Engineering, Arch, etc
            $table->string('avatar')->nullable(); // profile image
            $table->integer('daily_limit_hours')->nullable(); // limit set from settings later
            $table->timestamp('accepted_terms_at')->nullable();

            $table->enum('status', ['active', 'disabled', 'blocked'])->default('active'); // final version

            $table->integer('warnings')->default(0);
            $table->enum('role_type', [
                'admin',
                'staff',
                'faculty',
                'staff_faculty'
            ])->default('staff');


            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');

            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
