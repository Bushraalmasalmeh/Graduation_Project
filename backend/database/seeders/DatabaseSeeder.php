<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // أولاً: تشغيل CoreDataSeeder (هو اللي بيخلق المحطات والكابينات)
        $this->call(CoreDataSeeder::class);

        // ثانياً: إنشاء 10 مستخدمين عشوائيين (اختياري)
        // \App\Models\User::factory(10)->create();

        // لا تقم بإنشاء Charger هنا - لأنه تم إنشاؤه في CoreDataSeeder

        $this->command->info('✅ Database seeded successfully!');
        $this->command->info('📧 Admin Login: admin@electra.com / 123456');
        $this->command->info('📧 User Login: islam@test.com / 123456');
        $this->command->info('🔌 Charger UID for testing: 911');
        $this->command->info('👤 Job Number for testing: 1111');
    }
}
