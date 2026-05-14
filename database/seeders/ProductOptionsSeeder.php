<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductOptionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('product_options')->insert([
            [
                'product_id' => 1,
                'user_id' => 1,
                'option_type' => 'Color',
                'option_name' => 'Red',
                'price' => 10.00,
                'image_url' => null,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            // Add more as needed
        ]);
    }
}