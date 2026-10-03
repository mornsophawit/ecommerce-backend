<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            StatusesSeeder::class,
            StoreAndBranchSeeder::class,
            ProductCatalogSeeder::class,
            // CustomerTransactionsSeeder::class,

            // AddressSeeder::class,
            // ProductTypeSeeder::class,
            // ProductCategorySeeder::class,
            // ProductSeeder::class,
            // ProductOptionsSeeder::class,
            // CartsSeeder::class,
            // CartItemsSeeder::class,
            // OrdersSeeder::class,
            // OrderItemsSeeder::class,
            // PaymentsSeeder::class,
            // RefundsSeeder::class,
        ]);
    }
}
