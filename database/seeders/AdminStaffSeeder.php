<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Staff;
use App\Models\Clinic;
use Illuminate\Support\Facades\Hash;

class AdminStaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure default Clinic exists
        $clinic = Clinic::firstOrCreate(
            ['code' => 'CLN-LON'],
            [
                'name' => 'Insight Main Healthcare Clinic',
                'address' => '123 Healthcare Way, London, UK',
                'phone' => '+44 20 7946 0912',
                'email' => 'info@insighthealth.co.uk',
                'operating_hours' => [
                    'Mon-Fri' => '08:00 - 18:00',
                    'Sat' => '09:00 - 16:00'
                ],
                'total_rooms' => 5,
                'status' => 'active'
            ]
        );

        // 2. Define the 4 Admin Role Test Users
        $accounts = [
            [
                'username' => 'superadmin',
                'email' => 'superadmin@insight.com',
                'password' => 'Password123!',
                'name' => 'Sarah Connor',
                'first_name' => 'Sarah',
                'last_name' => 'Connor',
                'role' => 'super_admin',
                'staff_code' => 'STF-0001',
                'specialization' => 'Global Governance & Operations',
                'phone' => '+44 7700 900001',
            ],
            [
                'username' => 'admin',
                'email' => 'admin@insight.com',
                'password' => 'Password123!',
                'name' => 'Michael Chang',
                'first_name' => 'Michael',
                'last_name' => 'Chang',
                'role' => 'administrator',
                'staff_code' => 'STF-0002',
                'specialization' => 'Clinic Management & Operations',
                'phone' => '+44 7700 900002',
            ],
            [
                'username' => 'reception',
                'email' => 'reception@insight.com',
                'password' => 'Password123!',
                'name' => 'Elena Rostova',
                'first_name' => 'Elena',
                'last_name' => 'Rostova',
                'role' => 'reception',
                'staff_code' => 'STF-0003',
                'specialization' => 'Front Desk & Patient Intake',
                'phone' => '+44 7700 900003',
            ],
            [
                'username' => 'clinician',
                'email' => 'clinician@insight.com',
                'password' => 'Password123!',
                'name' => 'Dr. Marcus Thorne',
                'first_name' => 'Marcus',
                'last_name' => 'Thorne',
                'role' => 'clinician',
                'staff_code' => 'STF-0004',
                'specialization' => 'Lead Sonographer & Obstetrics',
                'phone' => '+44 7700 900004',
            ],
        ];

        foreach ($accounts as $acc) {
            // Create or update User
            $user = User::updateOrCreate(
                ['email' => $acc['email']],
                [
                    'name' => $acc['name'],
                    'password' => Hash::make($acc['password']),
                ]
            );

            // Create or update Staff Profile
            Staff::updateOrCreate(
                ['email' => $acc['email']],
                [
                    'user_id' => $user->id,
                    'staff_code' => $acc['staff_code'],
                    'first_name' => $acc['first_name'],
                    'last_name' => $acc['last_name'],
                    'phone' => $acc['phone'],
                    'role' => $acc['role'],
                    'clinic_id' => $clinic->id,
                    'specialization' => $acc['specialization'],
                    'status' => 'active',
                ]
            );
        }
    }
}
