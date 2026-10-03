<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Store;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StoreAndBranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Parent Store profile
        $store = Store::create([
            'name' => 'Chip Mong Supermarket',
            'name_kh' => 'ជីប ម៉ុង ស៊ូពើម៉ាកែត',
            'profile_url' => 'https://example.com',
            'cover_url' => 'https://example.com',
            'description' => 'Premium general shopping mart chain.',
            'description_kh' => 'ហាងទំនិញលំដាប់ខ្ពស់',
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        // 2. Seed Child Outlets linked directly via foreign keys
        Branch::create([
            'store_id' => $store->id,
            'name' => 'Tuol Kork Branch',
            'name_kh' => 'សាខាទួលគោក',
            'address' => 'Street 289, Phnom Penh, Cambodia',
            'address_kh' => 'ផ្លូវ ២៨៩, ខណ្ឌទួលគោក, ភ្នំពេញ',
            'map_url' => 'https://google.com',
            'created_by' => 2, // Created by our Store Admin account profile
            'updated_by' => 2,
        ]);

        Branch::create([
            'store_id' => $store->id,
            'name' => 'BKK1 Express',
            'name_kh' => 'សាខាបឹងកេងកង១',
            'address' => 'Street 51, Phnom Penh, Cambodia',
            'address_kh' => 'ផ្លូវ ៥១, ខណ្ឌបឹងកេងកង, ភ្នំពេញ',
            'map_url' => 'https://google.com',
            'created_by' => 2,
            'updated_by' => 2,
        ]);
    }
}
