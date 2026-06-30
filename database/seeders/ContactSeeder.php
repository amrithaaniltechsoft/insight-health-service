<?php

namespace Database\Seeders;

use App\Models\Contact;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    public function run(): void
    {
        Contact::create([
            'contact1' => '+968 1234 5678',
            'contact2' => '+968 8765 4321',
            'email' => 'info@insighthealthservices.com',
            'address' => "Shop #04, Building #1//2210\nBlock #415, Al Wafa Street\nAmerat, Oman",
            'mon_fri' => '08:00 - 21:00',
            'saturday' => '08:00 - 21:00',
            'sunday' => '08:00 - 21:00',
        ]);
    }
}
