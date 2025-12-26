<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Car;
use App\Models\ChargerStation;
use App\Models\Cabinet;
use App\Models\Charger;
use App\Models\ContactMessage;
use App\Models\UsageSession;

class CoreDataSeeder extends Seeder
{
    public function run(): void
    {
        // ======================
        // 1) SETTINGS
        // ======================
        \App\Models\Setting::factory()->create();

        // ======================
        // 2) ADMIN USER
        // ======================
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@electra.com',
            'password' => bcrypt('123456'),
            'job_number' => '9000',
            'role_type' => 'admin',
            'department' => 'IT',
        ]);

        // ======================
        // 3) STATIONS + CABINETS + CHARGERS + UID
        // ======================
        $stations = [
            [
                'name' => 'IT',
                'code' => 9,
                'cabinets' => 2,
                'department' => 'IT',
                'description' => 'Main EV charging station near IT College'
            ],
            [
                'name' => 'Health Center',
                'code' => 15,
                'cabinets' => 1,
                'department' => 'Medical',
                'description' => 'Station near the Health Center entrance'
            ],
            [
                'name' => 'Business',
                'code' => 18,
                'cabinets' => 2,
                'department' => 'Business',
                'description' => 'Station beside Business College building and library building'
            ],
            [
                'name' => 'Engineering',
                'code' => 19,
                'cabinets' => 1,
                'department' => 'Engineering',
                'description' => 'EV chargers near Engineering College building and buses stop'
            ],
            [
                'name' => 'Sport Center',
                'code' => 7,
                'cabinets' => 1,
                'department' => 'Sports',
                'description' => 'EV charging behind the Sport Center'
            ],
        ];

        foreach ($stations as $s) {

            $station = ChargerStation::create([
                'station_code' => $s['code'],
                'station_name' => $s['name'],
                'location' => $s['name'],
                'department' => $s['department'],
                'total_cabinets' => $s['cabinets'],
                'status' => 'active',
                'description' => $s['description'],
            ]);

            for ($c = 1; $c <= $s['cabinets']; $c++) {

                $cabinet = Cabinet::create([
                    'station_id' => $station->id,
                    'cabinet_number' => $c,
                    'total_chargers' => 2,
                    'status' => 'available',
                ]);

                for ($ch = 1; $ch <= 2; $ch++) {

                    $uid = $s['code'] . $c . $ch;

                    Charger::create([
                        'cabinet_id' => $cabinet->id,
                        'charger_number' => $ch,
                        'uid' => $uid,
                        'status' => 'available',
                    ]);
                }
            }
        }

        // ======================
        // 4) DEMO USERS
        // ======================

        $user1 = User::create([
            'name' => 'Islam Noor',
            'email' => 'islam@test.com',
            'password' => bcrypt('123456'),
            'job_number' => '1111',
            'department' => 'IT',
            'role_type' => 'staff',
        ]);

        $user2 = User::create([
            'name' => 'Fares',
            'email' => 'fares@test.com',
            'password' => bcrypt('123456'),
            'job_number' => '2222',
            'department' => 'IT',
            'role_type' => 'staff',
        ]);

        // ======================
        // 5) CARS
        // ======================
        Car::create([
            'user_id' => $user1->id,
            'car_model' => 'Volkswagen ID4',
            'plate_number' => '20-12345',
        ]);

        Car::create([
            'user_id' => $user2->id,
            'car_model' => 'BYD Atto 3',
            'plate_number' => '20-98765',
        ]);

        // ======================
        // 6) CONTACT MESSAGES
        // ======================
        ContactMessage::create([
            'user_id' => $user1->id,
            'name' => 'Islam Noor',
            'email' => 'islam@test.com',
            'phone' => '0790000000',
            'message' => 'The charger near IT College is not working.',
            'status' => 'pending',  // OK
        ]);

        ContactMessage::create([
            'user_id' => $user2->id,
            'name' => 'Fares',
            'email' => 'fares@test.com',
            'phone' => '0791111111',
            'message' => 'LCD screen shows an error.',
            'status' => 'replied',   // OK (بدال resolved)
            'admin_reply' => 'We fixed the issue.',
        ]);
    }
}
