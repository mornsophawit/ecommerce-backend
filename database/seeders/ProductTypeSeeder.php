<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductType;

class ProductTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Electronics',
            'Clothing',
            'Books',
            'Home & Garden',
            'Sports',
            'Toys',
            'Beauty',
            'Automotive',
            'Health',
            'Food',
            'Jewelry',
            'Music',
            'Movies',
            'Games',
            'Furniture'
        ];

        $imageUrls = [
            'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=640&h=480&fit=crop',
            'https://images.unsplash.com/photo-1445205170230-053b83016050?w=640&h=480&fit=crop',
            'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=640&h=480&fit=crop',
            'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=640&h=480&fit=crop',
            'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=640&h=480&fit=crop',
            'https://images.unsplash.com/photo-1558877385-1199c1af4e0f?w=640&h=480&fit=crop',
            'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=640&h=480&fit=crop',
            'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=640&h=480&fit=crop',
            'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=640&h=480&fit=crop',
            'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=640&h=480&fit=crop',
            'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=640&h=480&fit=crop',
            'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=640&h=480&fit=crop',
            'https://images.unsplash.com/photo-1489599735734-79b4e62e3c0?w=640&h=480&fit=crop',
            'https://images.unsplash.com/photo-1556438064-2d7646166914?w=640&h=480&fit=crop',
            'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=640&h=480&fit=crop'
        ];

        foreach ($categories as $index => $category) {
            ProductType::create([
                'name' => $category,
                'img_url' => $imageUrls[$index],
            ]);
        }
    }
}
