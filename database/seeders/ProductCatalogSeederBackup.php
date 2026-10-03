<!-- <?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use App\Models\ProductType;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;

class ProductCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Parent Product Category
        $beveragesCategory = ProductCategory::create([
            'parent_id' => null,
            'name' => 'Food & Beverages',
            'name_kh' => 'អាហារ និងភេសជ្ជៈ',
            'slug' => 'food-and-beverages',
            'description' => 'Delicious refreshments and local snacks.',
            'description_kh' => 'ភេសជ្ជៈស្រស់ស្រាយ និងអាហារសម្រន់ក្នុងស្រុក។',
            'image_url' => 'https://example.com',
            'icon' => 'fa-coffee',
            'is_active' => true,
            'display_order' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        // 2. Seed Product Types tied directly to the Category
        $coffeeType = ProductType::create([
            'category_id' => $beveragesCategory->id,
            'name' => 'Coffee Drinks',
            'name_kh' => 'ភេសជ្ជៈកាហ្វេ',
            'img_url' => 'https://example.com',
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        $sodaType = ProductType::create([
            'category_id' => $beveragesCategory->id,
            'name' => 'Soft Drinks',
            'name_kh' => 'ភេសជ្ជៈហ្គាស',
            'img_url' => 'https://example.com',
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        // ==========================================
        // ITEM A: COFFEE PRODUCT
        // ==========================================
        $coffeeProduct = Product::create([
            'product_type_id' => $coffeeType->id,
            'name' => 'Iced Latte',
            'name_kh' => 'ឡាតេទឹកកក',
            'description' => 'Premium espresso with smooth fresh milk.',
            'description_kh' => 'អេសប្រេសសូគុណភាពខ្ពស់ ជាមួយទឹកដោះគោស្រស់ដ៏ឈ្ងុយឆ្ងាញ់។',
            'price' => 2.50,
            'stock' => 150,
            'unit_value' => 1.0000,
            'unit_name' => 'Cup',
            'unit_name_kh' => 'កែវ',
            'created_by' => 2, // Admin Store Manager footprint
            'updated_by' => 2,
        ]);

        // Options for Coffee Product (Sizes & Variations)
        ProductOption::create([
            'product_id' => $coffeeProduct->id,
            'option_type' => 'Size',
            'option_type_kh' => 'ទំហំ',
            'option_name' => 'Medium',
            'option_name_kh' => 'មធ្យម',
            'price' => 0.00, // No extra charge for baseline standard
            'image_url' => null,
            'created_by' => 2,
            'updated_by' => 2,
        ]);

        ProductOption::create([
            'product_id' => $coffeeProduct->id,
            'option_type' => 'Size',
            'option_type_kh' => 'ទំហំ',
            'option_name' => 'Large',
            'option_name_kh' => 'ធំ',
            'price' => 0.50, // Extra $0.50 charge
            'image_url' => null,
            'created_by' => 2,
            'updated_by' => 2,
        ]);

        // Gallery Images for Coffee Product
        ProductImage::create([
            'product_id' => $coffeeProduct->id,
            'image_url' => 'https://example.com',
            'is_primary' => true,
            'created_by' => 2,
            'updated_by' => 2,
        ]);


        // ==========================================
        // ITEM B: SODA PRODUCT
        // ==========================================
        $sodaProduct = Product::create([
            'product_type_id' => $sodaType->id,
            'name' => 'Coca-Cola Can',
            'name_kh' => 'កូកាកូឡា កំប៉ុង',
            'description' => 'Classic refreshing carbonated soda.',
            'description_kh' => 'ភេសជ្ជៈហ្គាស កូកាកូឡា រសជាតិដើម ស្រស់ស្រាយ។',
            'price' => 0.80,
            'stock' => 500,
            'unit_value' => 1.0000,
            'unit_name' => 'Can',
            'unit_name_kh' => 'កំប៉ុង',
            'created_by' => 2,
            'updated_by' => 2,
        ]);

        // Baseline pricing option row mapping matching structure logic rules
        ProductOption::create([
            'product_id' => $sodaProduct->id,
            'option_type' => 'Packaging',
            'option_type_kh' => 'ការវេចខ្ចប់',
            'option_name' => 'Standard Can',
            'option_name_kh' => 'កំប៉ុងធម្មតា',
            'price' => 0.00,
            'image_url' => null,
            'created_by' => 2,
            'updated_by' => 2,
        ]);

        ProductImage::create([
            'product_id' => $sodaProduct->id,
            'image_url' => 'https://example.com',
            'is_primary' => true,
            'created_by' => 2,
            'updated_by' => 2,
        ]);
    }
} -->
