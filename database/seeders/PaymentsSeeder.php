<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('payments')->insert([
            [
                'order_id' => 1,
                'status_id' => 1,
                'method' => 'card',
                'created_by' => 1,
            ],
            // Add more as needed
        ]);
    }
}