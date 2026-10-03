<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Address;
use App\Models\User;

class AddressSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            return;
        }

        $addresses = [
            [
                'user_id'    => $user->id,
                'title'      => 'Home',
                'address'    => '123 Monivong Blvd, Phnom Penh, Cambodia',
                'contact'    => '+85512345678',
                'lat'        => 11.5564,
                'long'       => 104.9282,
                'is_default' => true,
            ],
            [
                'user_id'    => $user->id,
                'title'      => 'Office',
                'address'    => '456 Norodom Blvd, BKK1, Phnom Penh, Cambodia',
                'contact'    => '+85598765432',
                'lat'        => 11.5625,
                'long'       => 104.9230,
                'is_default' => false,
            ],
            [
                'user_id'    => $user->id,
                'title'      => 'Warehouse',
                'address'    => '789 Russian Blvd, Toul Kork, Phnom Penh, Cambodia',
                'contact'    => '+85511223344',
                'lat'        => 11.5793,
                'long'       => 104.9100,
                'is_default' => false,
            ],
            [
                'user_id'    => 2,
                'title'      => 'Home',
                'address'    => '799 Russian Blvd, Toul Kork, Phnom Penh, Cambodia',
                'contact'    => '+85511223345',
                'lat'        => 11.5993,
                'long'       => 104.9100,
                'is_default' => true,
            ],
        ];

        foreach ($addresses as $data) {
            Address::create($data);
        }
    }
}
