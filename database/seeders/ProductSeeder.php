<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productsData = [
            1 => [ // Electronics
                ['name' => 'Laptop', 'description' => 'High-performance laptop for work and gaming.', 'price' => 1200.00, 'stock' => 50, 'img_url' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=640&h=480&fit=crop'],
                ['name' => 'Smartphone', 'description' => 'Latest smartphone with advanced features.', 'price' => 800.00, 'stock' => 100, 'img_url' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=640&h=480&fit=crop'],
                ['name' => 'Headphones', 'description' => 'Noise-cancelling wireless headphones.', 'price' => 200.00, 'stock' => 75, 'img_url' => 'https://images.unsplash.com/photo-1484704849700-f032a568e944?w=640&h=480&fit=crop'],
            ],
            2 => [ // Clothing
                ['name' => 'T-Shirt', 'description' => 'Comfortable cotton t-shirt.', 'price' => 20.00, 'stock' => 200, 'img_url' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=640&h=480&fit=crop'],
                ['name' => 'Jeans', 'description' => 'Classic blue jeans.', 'price' => 50.00, 'stock' => 150, 'img_url' => 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=640&h=480&fit=crop'],
                ['name' => 'Jacket', 'description' => 'Warm winter jacket.', 'price' => 100.00, 'stock' => 80, 'img_url' => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=640&h=480&fit=crop'],
            ],
            3 => [ // Books
                ['name' => 'Novel', 'description' => 'Bestselling fiction novel.', 'price' => 15.00, 'stock' => 300, 'img_url' => 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=640&h=480&fit=crop'],
                ['name' => 'Textbook', 'description' => 'Educational textbook for students.', 'price' => 80.00, 'stock' => 50, 'img_url' => 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=640&h=480&fit=crop'],
                ['name' => 'Cookbook', 'description' => 'Recipes for delicious meals.', 'price' => 25.00, 'stock' => 100, 'img_url' => 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=640&h=480&fit=crop'],
            ],
            4 => [ // Home & Garden
                ['name' => 'Sofa', 'description' => 'Comfortable living room sofa.', 'price' => 500.00, 'stock' => 20, 'img_url' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=640&h=480&fit=crop'],
                ['name' => 'Plant', 'description' => 'Indoor decorative plant.', 'price' => 30.00, 'stock' => 150, 'img_url' => 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=640&h=480&fit=crop'],
                ['name' => 'Grill', 'description' => 'Outdoor barbecue grill.', 'price' => 150.00, 'stock' => 40, 'img_url' => 'https://images.unsplash.com/photo-1559314809-0d155014e29e?w=640&h=480&fit=crop'],
            ],
            5 => [ // Sports
                ['name' => 'Basketball', 'description' => 'Official size basketball.', 'price' => 40.00, 'stock' => 100, 'img_url' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=640&h=480&fit=crop'],
                ['name' => 'Running Shoes', 'description' => 'Comfortable running shoes.', 'price' => 120.00, 'stock' => 80, 'img_url' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=640&h=480&fit=crop'],
                ['name' => 'Dumbbells', 'description' => 'Set of adjustable dumbbells.', 'price' => 200.00, 'stock' => 30, 'img_url' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=640&h=480&fit=crop'],
            ],
            6 => [ // Toys
                ['name' => 'Action Figure', 'description' => 'Superhero action figure.', 'price' => 15.00, 'stock' => 200, 'img_url' => 'https://images.unsplash.com/photo-1558877385-1199c1af4e0f?w=640&h=480&fit=crop'],
                ['name' => 'Puzzle', 'description' => '1000-piece jigsaw puzzle.', 'price' => 20.00, 'stock' => 150, 'img_url' => 'https://images.unsplash.com/photo-1606092195730-5d7b9af1efc5?w=640&h=480&fit=crop'],
                ['name' => 'Doll', 'description' => 'Cute fashion doll.', 'price' => 25.00, 'stock' => 100, 'img_url' => 'https://images.unsplash.com/photo-1594736797933-d0401ba2fe65?w=640&h=480&fit=crop'],
            ],
            7 => [ // Beauty
                ['name' => 'Lipstick', 'description' => 'Long-lasting lipstick.', 'price' => 15.00, 'stock' => 300, 'img_url' => 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=640&h=480&fit=crop'],
                ['name' => 'Shampoo', 'description' => 'Nourishing hair shampoo.', 'price' => 10.00, 'stock' => 200, 'img_url' => 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=640&h=480&fit=crop'],
                ['name' => 'Perfume', 'description' => 'Elegant fragrance.', 'price' => 50.00, 'stock' => 100, 'img_url' => 'https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?w=640&h=480&fit=crop'],
            ],
            8 => [ // Automotive
                ['name' => 'Car Tires', 'description' => 'Durable car tires.', 'price' => 300.00, 'stock' => 50, 'img_url' => 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=640&h=480&fit=crop'],
                ['name' => 'Motor Oil', 'description' => 'High-quality motor oil.', 'price' => 20.00, 'stock' => 150, 'img_url' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=640&h=480&fit=crop'],
                ['name' => 'Car Battery', 'description' => 'Reliable car battery.', 'price' => 100.00, 'stock' => 80, 'img_url' => 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=640&h=480&fit=crop'],
            ],
            9 => [ // Health
                ['name' => 'Vitamins', 'description' => 'Multivitamin supplement.', 'price' => 25.00, 'stock' => 200, 'img_url' => 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=640&h=480&fit=crop'],
                ['name' => 'Yoga Mat', 'description' => 'Non-slip yoga mat.', 'price' => 30.00, 'stock' => 100, 'img_url' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=640&h=480&fit=crop'],
                ['name' => 'Scale', 'description' => 'Digital bathroom scale.', 'price' => 40.00, 'stock' => 150, 'img_url' => 'https://images.unsplash.com/photo-1575311373937-040b8e1fd5b6?w=640&h=480&fit=crop'],
            ],
            10 => [ // Food
                ['name' => 'Chocolate', 'description' => 'Delicious dark chocolate bar.', 'price' => 5.00, 'stock' => 500, 'img_url' => 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=640&h=480&fit=crop'],
                ['name' => 'Coffee', 'description' => 'Premium ground coffee.', 'price' => 15.00, 'stock' => 200, 'img_url' => 'https://images.unsplash.com/photo-1497935586351-b67a49e012bf?w=640&h=480&fit=crop'],
                ['name' => 'Pasta', 'description' => 'Organic pasta noodles.', 'price' => 8.00, 'stock' => 300, 'img_url' => 'https://images.unsplash.com/photo-1621996346565-e3dbc353d2e5?w=640&h=480&fit=crop'],
            ],
            11 => [ // Jewelry
                ['name' => 'Necklace', 'description' => 'Elegant gold necklace.', 'price' => 200.00, 'stock' => 50, 'img_url' => 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=640&h=480&fit=crop'],
                ['name' => 'Ring', 'description' => 'Diamond engagement ring.', 'price' => 1000.00, 'stock' => 20, 'img_url' => 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=640&h=480&fit=crop'],
                ['name' => 'Earrings', 'description' => 'Silver hoop earrings.', 'price' => 50.00, 'stock' => 100, 'img_url' => 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=640&h=480&fit=crop'],
            ],
            12 => [ // Music
                ['name' => 'Guitar', 'description' => 'Acoustic guitar.', 'price' => 300.00, 'stock' => 30, 'img_url' => 'https://images.unsplash.com/photo-1510915361894-db8b60106cb1?w=640&h=480&fit=crop'],
                ['name' => 'Speakers', 'description' => 'Wireless Bluetooth speakers.', 'price' => 150.00, 'stock' => 60, 'img_url' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=640&h=480&fit=crop'],
                ['name' => 'Vinyl Record', 'description' => 'Classic vinyl record.', 'price' => 20.00, 'stock' => 200, 'img_url' => 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=640&h=480&fit=crop'],
            ],
            13 => [ // Movies
                ['name' => 'DVD', 'description' => 'Action movie DVD.', 'price' => 10.00, 'stock' => 300, 'img_url' => 'https://images.unsplash.com/photo-1489599735734-79b4e62e3c0?w=640&h=480&fit=crop'],
                ['name' => 'Blu-ray', 'description' => 'High-definition Blu-ray disc.', 'price' => 15.00, 'stock' => 200, 'img_url' => 'https://images.unsplash.com/photo-1489599735734-79b4e62e3c0?w=640&h=480&fit=crop'],
                ['name' => 'Streaming Device', 'description' => 'Media streaming device.', 'price' => 50.00, 'stock' => 100, 'img_url' => 'https://images.unsplash.com/photo-1593784991095-a205069470b6?w=640&h=480&fit=crop'],
            ],
            14 => [ // Games
                ['name' => 'Board Game', 'description' => 'Fun family board game.', 'price' => 30.00, 'stock' => 150, 'img_url' => 'https://images.unsplash.com/photo-1556438064-2d7646166914?w=640&h=480&fit=crop'],
                ['name' => 'Video Game', 'description' => 'Exciting video game.', 'price' => 60.00, 'stock' => 100, 'img_url' => 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=640&h=480&fit=crop'],
                ['name' => 'Cards', 'description' => 'Deck of playing cards.', 'price' => 5.00, 'stock' => 400, 'img_url' => 'https://images.unsplash.com/photo-1606092195730-5d7b9af1efc5?w=640&h=480&fit=crop'],
            ],
            15 => [ // Furniture
                ['name' => 'Chair', 'description' => 'Ergonomic office chair.', 'price' => 150.00, 'stock' => 50, 'img_url' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=640&h=480&fit=crop'],
                ['name' => 'Table', 'description' => 'Dining room table.', 'price' => 300.00, 'stock' => 30, 'img_url' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=640&h=480&fit=crop'],
                ['name' => 'Lamp', 'description' => 'Modern desk lamp.', 'price' => 40.00, 'stock' => 100, 'img_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=640&h=480&fit=crop'],
            ],
        ];

        foreach ($productsData as $typeId => $products) {
            foreach ($products as $product) {
                Product::create([
                    'product_type_id' => $typeId,
                    'user_id' => rand(1, 5),
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'price' => $product['price'],
                    'stock' => $product['stock'],
                    'img_url' => $product['img_url'],
                ]);
            }
        }
    }
}
