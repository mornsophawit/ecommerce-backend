<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductCategory;
use Illuminate\Support\Str;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'title' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'Electronic devices and gadgets',
                'image_url' => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=640&h=480&fit=crop',
                'icon' => 'fas fa-laptop',
                'parent_id' => null,
                'display_order' => 1,
            ],
            [
                'title' => 'Clothing',
                'slug' => 'clothing',
                'description' => 'Fashion and apparel',
                'image_url' => 'https://images.unsplash.com/photo-1445205170230-053b83016050?w=640&h=480&fit=crop',
                'icon' => 'fas fa-tshirt',
                'parent_id' => null,
                'display_order' => 2,
            ],
            [
                'title' => 'Books',
                'slug' => 'books',
                'description' => 'Books and literature',
                'image_url' => 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=640&h=480&fit=crop',
                'icon' => 'fas fa-book',
                'parent_id' => null,
                'display_order' => 3,
            ],
            [
                'title' => 'Home & Garden',
                'slug' => 'home-garden',
                'description' => 'Home improvement and gardening',
                'image_url' => 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=640&h=480&fit=crop',
                'icon' => 'fas fa-home',
                'parent_id' => null,
                'display_order' => 4,
            ],
            [
                'title' => 'Sports',
                'slug' => 'sports',
                'description' => 'Sports equipment and accessories',
                'image_url' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=640&h=480&fit=crop',
                'icon' => 'fas fa-futbol',
                'parent_id' => null,
                'display_order' => 5,
            ],
            [
                'title' => 'Toys',
                'slug' => 'toys',
                'description' => 'Toys and games for children',
                'image_url' => 'https://images.unsplash.com/photo-1558877385-1199c1af4e0f?w=640&h=480&fit=crop',
                'icon' => 'fas fa-gamepad',
                'parent_id' => null,
                'display_order' => 6,
            ],
            [
                'title' => 'Beauty',
                'slug' => 'beauty',
                'description' => 'Beauty and personal care products',
                'image_url' => 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=640&h=480&fit=crop',
                'icon' => 'fas fa-spa',
                'parent_id' => null,
                'display_order' => 7,
            ],
            [
                'title' => 'Automotive',
                'slug' => 'automotive',
                'description' => 'Car parts and automotive accessories',
                'image_url' => 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=640&h=480&fit=crop',
                'icon' => 'fas fa-car',
                'parent_id' => null,
                'display_order' => 8,
            ],
            [
                'title' => 'Health',
                'slug' => 'health',
                'description' => 'Health and wellness products',
                'image_url' => 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=640&h=480&fit=crop',
                'icon' => 'fas fa-heartbeat',
                'parent_id' => null,
                'display_order' => 9,
            ],
            [
                'title' => 'Food',
                'slug' => 'food',
                'description' => 'Food and beverages',
                'image_url' => 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=640&h=480&fit=crop',
                'icon' => 'fas fa-utensils',
                'parent_id' => null,
                'display_order' => 10,
            ],
            [
                'title' => 'Jewelry',
                'slug' => 'jewelry',
                'description' => 'Jewelry and accessories',
                'image_url' => 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=640&h=480&fit=crop',
                'icon' => 'fas fa-gem',
                'parent_id' => null,
                'display_order' => 11,
            ],
            [
                'title' => 'Music',
                'slug' => 'music',
                'description' => 'Music instruments and accessories',
                'image_url' => 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=640&h=480&fit=crop',
                'icon' => 'fas fa-music',
                'parent_id' => null,
                'display_order' => 12,
            ],
            [
                'title' => 'Movies',
                'slug' => 'movies',
                'description' => 'Movies and entertainment',
                'image_url' => 'https://images.unsplash.com/photo-1489599735734-79b4e62e3c0?w=640&h=480&fit=crop',
                'icon' => 'fas fa-film',
                'parent_id' => null,
                'display_order' => 13,
            ],
            [
                'title' => 'Games',
                'slug' => 'games',
                'description' => 'Video games and board games',
                'image_url' => 'https://images.unsplash.com/photo-1556438064-2d7646166914?w=640&h=480&fit=crop',
                'icon' => 'fas fa-dice',
                'parent_id' => null,
                'display_order' => 14,
            ],
            [
                'title' => 'Furniture',
                'slug' => 'furniture',
                'description' => 'Home furniture and decor',
                'image_url' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=640&h=480&fit=crop',
                'icon' => 'fas fa-couch',
                'parent_id' => null,
                'display_order' => 15,
            ],
        ];

        foreach ($categories as $category) {
            ProductCategory::create([
                'parent_id' => $category['parent_id'],
                'title' => $category['title'],
                'slug' => $category['slug'],
                'description' => $category['description'],
                'image_url' => $category['image_url'],
                'icon' => $category['icon'],
                'is_active' => true,
                'display_order' => $category['display_order'],
                'created_by' => 1, // Assuming user 1 exists
                'updated_by' => 1,
            ]);
        }

        // Add some subcategories for hierarchy
        $subcategories = [
            [
                'title' => 'Laptops',
                'slug' => 'laptops',
                'description' => 'Portable computers',
                'parent_id' => 1, // Electronics
                'display_order' => 1,
            ],
            [
                'title' => 'Smartphones',
                'slug' => 'smartphones',
                'description' => 'Mobile phones',
                'parent_id' => 1, // Electronics
                'display_order' => 2,
            ],
            [
                'title' => 'T-Shirts',
                'slug' => 't-shirts',
                'description' => 'Casual shirts',
                'parent_id' => 2, // Clothing
                'display_order' => 1,
            ],
            [
                'title' => 'Jeans',
                'slug' => 'jeans',
                'description' => 'Denim pants',
                'parent_id' => 2, // Clothing
                'display_order' => 2,
            ],
        ];

        foreach ($subcategories as $subcategory) {
            ProductCategory::create([
                'parent_id' => $subcategory['parent_id'],
                'title' => $subcategory['title'],
                'slug' => $subcategory['slug'],
                'description' => $subcategory['description'],
                'image_url' => null,
                'icon' => null,
                'is_active' => true,
                'display_order' => $subcategory['display_order'],
                'created_by' => 1,
                'updated_by' => 1,
            ]);
        }
    }
}
