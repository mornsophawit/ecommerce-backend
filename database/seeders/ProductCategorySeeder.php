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
                'name' => 'Electronics',
                'name_kh'=> 'គ្រឿងអេឡិចត្រូនិច',
                'slug' => 'electronics',
                'description' => 'Electronic devices and gadgets',
                'description_kh'=> 'ឧបករណ៍អេឡិចត្រូនិច និងឧបករណ៍អេឡិចត្រូនិច',
                'image_url' => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=640&h=480&fit=crop',
                'icon' => 'fas fa-laptop',
                'parent_id' => null,
                'display_order' => 1,
            ],
            [
                'name' => 'Clothing',
                'name_kh' => 'សម្លៀកបំពាក់',
                'slug' => 'clothing',
                'description' => 'Fashion and apparel',
                'description_kh'=> null,
                'image_url' => 'https://images.unsplash.com/photo-1445205170230-053b83016050?w=640&h=480&fit=crop',
                'icon' => 'fas fa-tshirt',
                'parent_id' => null,
                'display_order' => 2,
            ],
            [
                'name' => 'Books',
                'name_kh' => 'សៀវភៅ',
                'slug' => 'books',
                'description' => 'Books and literature',
                'description_kh'=> null,
                'image_url' => 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=640&h=480&fit=crop',
                'icon' => 'fas fa-book',
                'parent_id' => null,
                'display_order' => 3,
            ],
            [
                'name' => 'Home & Garden',
                'name_kh' => 'ផ្ទះ និងសួនច្បារ',
                'slug' => 'home-garden',
                'description' => 'Home improvement and gardening',
                'description_kh'=> null,
                'image_url' => 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=640&h=480&fit=crop',
                'icon' => 'fas fa-home',
                'parent_id' => null,
                'display_order' => 4,
            ],
            [
                'name' => 'Sports',
                'name_kh' => 'កីឡា',
                'slug' => 'sports',
                'description' => 'Sports equipment and accessories',
                'description_kh'=> null,
                'image_url' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=640&h=480&fit=crop',
                'icon' => 'fas fa-futbol',
                'parent_id' => null,
                'display_order' => 5,
            ],
            [
                'name' => 'Toys',
                'name_kh' => 'ប្រដាប់ក្មេងលេង',
                'slug' => 'toys',
                'description' => 'Toys and games for children',
                'description_kh'=> null,
                'image_url' => 'https://images.unsplash.com/photo-1558877385-1199c1af4e0f?w=640&h=480&fit=crop',
                'icon' => 'fas fa-gamepad',
                'parent_id' => null,
                'display_order' => 6,
            ],
            [
                'name' => 'Beauty',
                'name_kh' => 'សម្រស់',
                'slug' => 'beauty',
                'description' => 'Beauty and personal care products',
                'description_kh'=> null,
                'image_url' => 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=640&h=480&fit=crop',
                'icon' => 'fas fa-spa',
                'parent_id' => null,
                'display_order' => 7,
            ],
            [
                'name' => 'Automotive',
                'name_kh' => 'យានយន្ត',
                'slug' => 'automotive',
                'description' => 'Car parts and automotive accessories',
                'description_kh'=> null,
                'image_url' => 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=640&h=480&fit=crop',
                'icon' => 'fas fa-car',
                'parent_id' => null,
                'display_order' => 8,
            ],
            [
                'name' => 'Health',
                'name_kh' => 'សុខភាព',
                'slug' => 'health',
                'description' => 'Health and wellness products',
                'description_kh'=> null,
                'image_url' => 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=640&h=480&fit=crop',
                'icon' => 'fas fa-heartbeat',
                'parent_id' => null,
                'display_order' => 9,
            ],
            [
                'name' => 'Food',
                'name_kh' => 'អាហារ',
                'slug' => 'food',
                'description' => 'Food and beverages',
                'description_kh'=> null,
                'image_url' => 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=640&h=480&fit=crop',
                'icon' => 'fas fa-utensils',
                'parent_id' => null,
                'display_order' => 10,
            ],
            [
                'name' => 'Jewelry',
                'name_kh' => 'គ្រឿងអលង្ការ',
                'slug' => 'jewelry',
                'description' => 'Jewelry and accessories',
                'description_kh'=> null,
                'image_url' => 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=640&h=480&fit=crop',
                'icon' => 'fas fa-gem',
                'parent_id' => null,
                'display_order' => 11,
            ],
            [
                'name' => 'Music',
                'name_kh' => 'តន្ត្រី',
                'slug' => 'music',
                'description' => 'Music instruments and accessories',
                'description_kh'=> null,
                'image_url' => 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=640&h=480&fit=crop',
                'icon' => 'fas fa-music',
                'parent_id' => null,
                'display_order' => 12,
            ],
            [
                'name' => 'Movies',
                'name_kh' => 'ភាពយន្ត',
                'slug' => 'movies',
                'description' => 'Movies and entertainment',
                'description_kh'=> null,
                'image_url' => 'https://images.unsplash.com/photo-1489599735734-79b4e62e3c0?w=640&h=480&fit=crop',
                'icon' => 'fas fa-film',
                'parent_id' => null,
                'display_order' => 13,
            ],
            [
                'name' => 'Games',
                'name_kh' => 'ហ្គេម',
                'slug' => 'games',
                'description' => 'Video games and board games',
                'description_kh'=> null,
                'image_url' => 'https://images.unsplash.com/photo-1556438064-2d7646166914?w=640&h=480&fit=crop',
                'icon' => 'fas fa-dice',
                'parent_id' => null,
                'display_order' => 14,
            ],
            [
                'name' => 'Furniture',
                'name_kh' => 'គ្រឿងសង្ហារិម',
                'slug' => 'furniture',
                'description' => 'Home furniture and decor',
                'description_kh'=> null,
                'image_url' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=640&h=480&fit=crop',
                'icon' => 'fas fa-couch',
                'parent_id' => null,
                'display_order' => 15,
            ],
        ];

        foreach ($categories as $category) {
            ProductCategory::create([
                'parent_id' => $category['parent_id'],
                'name' => $category['name'],
                'name_kh' => $category['name_kh'],
                'slug' => $category['slug'],
                'description' => $category['description'],
                'description_kh' => $category['description_kh'],
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
                'name' => 'Laptops',
                'name_kh' => 'ឡេបថប',
                'slug' => 'laptops',
                'description' => 'Portable computers',
                'description_kh'=> null,
                'parent_id' => 1, // Electronics
                'display_order' => 1,
            ],
            [
                'name' => 'Smartphones',
                'name_kh' => 'ទូរស័ព្ទដៃទំនើប',
                'slug' => 'smartphones',
                'description' => 'Mobile phones',
                'description_kh'=> null,
                'parent_id' => 1, // Electronics
                'display_order' => 2,
            ],
            [
                'name' => 'T-Shirts',
                'name_kh' => 'អាវយឺត',
                'slug' => 't-shirts',
                'description' => 'Casual shirts',
                'description_kh'=> null,
                'parent_id' => 2, // Clothing
                'display_order' => 1,
            ],
            [
                'name' => 'Jeans',
                'name_kh' => 'ខោរកាប៊យ',
                'slug' => 'jeans',
                'description' => 'Denim pants',
                'description_kh'=> null,
                'parent_id' => 2, // Clothing
                'display_order' => 2,
            ],
        ];

        foreach ($subcategories as $subcategory) {
            ProductCategory::create([
                'parent_id' => $subcategory['parent_id'],
                'name' => $subcategory['name'],
                'name_kh' => $subcategory['name_kh'],
                'slug' => $subcategory['slug'],
                'description' => $subcategory['description'],
                'description_kh' => $subcategory['description_kh'],
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
