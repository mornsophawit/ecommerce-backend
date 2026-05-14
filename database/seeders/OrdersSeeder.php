<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrdersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('orders')->insert([
            [
                'user_id' => 1,
                'status_id' => 1, // Pending
                'total_amount' => 100.00,
                'date' => now(),
            ],
            // Add more as needed
        ]);
    }
}