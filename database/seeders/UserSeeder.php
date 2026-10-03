<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Core Super Admin Account
        User::create([
            'id' => 1,
            'name' => 'System Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('123123'),
            'role' => 'super_admin',
            'created_by' => 1, // Self-referencing link configuration
            'updated_by' => 1,
        ]);

        // 2. Store Manager Account
        User::create([
            'id' => 2,
            'name' => 'Sok Store Admin',
            'email' => 'storeadmin@example.com',
            'password' => Hash::make('123123'),
            'role' => 'store_admin',
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        // 3. Shop Counter Cashier Account
        User::create([
            'id' => 3,
            'name' => 'Bopa Cashier',
            'email' => 'cashier@example.com',
            'password' => Hash::make('123123'),
            'role' => 'cashier',
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        // 4. Default Purchasing Customer Account
        User::create([
            'id' => 4,
            'name' => 'John Customer',
            'email' => 'customer@example.com',
            'password' => Hash::make('123123'),
            'role' => 'customer',
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        // 2. Store Manager Account
        User::create([
            'id' => 5,
            'name' => 'Dara Store Admin',
            'email' => 'storeadmindara@example.com',
            'password' => Hash::make('123123'),
            'role' => 'store_admin',
            'created_by' => 1,
            'updated_by' => 1,
        ]);
    }
}







// // Super Admin — global platform administrator
//         User::updateOrCreate(
//             ['email' => 'superadmin@gmail.com'],
//             [
//                 'name'     => 'Super Admin',
//                 'password' => Hash::make('TestPass'),
//                 'role'     => 'super_admin',
//             ]
//         );

//         // Store Admin — manages a store and its cashiers
//         User::updateOrCreate(
//             ['email' => 'storeadmin@gmail.com'],
//             [
//                 'name'     => 'Store Admin',
//                 'password' => Hash::make('TestPass'),
//                 'role'     => 'store_admin',
//             ]
//         );

//         // Cashier — POS / sells products in-store
//         User::updateOrCreate(
//             ['email' => 'cashier@gmail.com'],
//             [
//                 'name'     => 'Cashier',
//                 'password' => Hash::make('TestPass'),
//                 'role'     => 'cashier',
//             ]
//         );

//         // Customer — registered buyer
//         User::updateOrCreate(
//             ['email' => 'customer@gmail.com'],
//             [
//                 'name'     => 'Customer',
//                 'password' => Hash::make('123123'),
//                 'role'     => 'customer',
//             ]
//         );