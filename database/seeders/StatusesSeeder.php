<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('statuses')->insert([
            [
                'name' => 'Pending',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Processing',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Shipped',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Delivered',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Cancelled',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Refund Requested',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Refund Approved',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Refund Declined',
                'created_by' => 1,
                'updated_by' => 1,
            ],
        ]);
    }
}