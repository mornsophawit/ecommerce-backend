<?php

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
        // 1. Create the Main Skincare Category
        $skincareCategory = ProductCategory::create([
            'parent_id' => null,
            'name' => 'Skincare',
            'name_kh' => 'ថែរក្សាស្បែក',
            'slug' => 'skincare',
            'description' => 'Premium skincare routines and treatments.',
            'description_kh' => 'ផលិតផលថែរក្សាស្បែក និងព្យាបាលស្បែកកម្រិតខ្ពស់។',
            'image_url' => null,
            'icon' => 'spray-can-sparkles',
            'is_active' => true,
            'display_order' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        // ==========================================
        // STEP 1: CLEANSER
        // ==========================================
        $cleanserType = ProductType::create([
            'category_id' => $skincareCategory->id,
            'name' => 'Cleanser',
            'name_kh' => 'ហ្វូមលាងសម្អាតមុខ',
            'img_url' => 'https://example.com',
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        $pitCleanser = Product::create([
            'product_type_id' => $cleanserType->id,
            'name' => 'P.I.T CLEANSING MILK',
            'name_kh' => 'សំរាប់ស្បែកស្ងួត ជ្រួញយារធ្លាក់',
            'price' => 59.00,
            'unit_value' => 180,
            'unit_name' => 'ml',
            'unit_name_kh' => 'ម.ល',
            'created_by' => 2, 'updated_by' => 2,
        ]);
        // ProductOption::create(['product_id' => $pitCleanser->id, 'size' => '180ml', 'price' => 59.00]);

        $oilCutCleanser = Product::create([
            'product_type_id' => $cleanserType->id,
            'name' => 'ACSEN OIL CUT CLEANSING',
            'name_kh' => 'សំរាប់ស្បែកប្រេងមុន អាលែកហ្សី មានមុន',
            'price' => 44.00,
            'unit_value' => 120,
            'unit_name' => 'ml',
            'unit_name_kh' => 'ម.ល',
            'created_by' => 2, 'updated_by' => 2,
        ]);
        ProductOption::create(['product_id' => $oilCutCleanser->id, 'option_type' => 'promotion','option_type_kh' => 'ប្រម៉ូសិន', 'option_name' => '1+1', 'option_name_kh' => '១+១', 'price' => 60.00, 'created_by' => 2, 'updated_by' => 2]);
        ProductOption::create(['product_id' => $oilCutCleanser->id, 'option_type' => 'bigger','option_type_kh' => 'ធំ', 'option_name' => '300ml', 'option_name_kh' => '៣០០ មីលីលីត្រ', 'price' => 103.00, 'created_by' => 2, 'updated_by' => 2]);
        ProductOption::create(['product_id' => $oilCutCleanser->id, 'option_type' => 'bigger with promotion','option_type_kh' => 'ធំ ប្រម៉ូសិន', 'option_name' => '1+1 (300ml)', 'option_name_kh' => '១+១​ (៣០០ម.ល)', 'price' => 113.00, 'created_by' => 2, 'updated_by' => 2]);

        $oxygenCleanser = Product::create([
            'product_type_id' => $cleanserType->id,
            'name' => 'OXYGEN TONING CLEANSER',
            'name_kh' => 'សំរាប់ស្បែកងាយប្រតិកម្ម ចំហរ មានមុន',
            'price' => 52.00,
            'unit_value' => 150,
            'unit_name' => 'ml',
            'unit_name_kh' => 'ម.ល',
            'created_by' => 2, 'updated_by' => 2,
        ]);
        // ProductOption::create(['product_id' => $oxygenCleanser->id, 'size' => '150ml', 'price' => 52.00]);


        // ==========================================
        // STEP 2: MIST / TONER
        // ==========================================
        $tonerType = ProductType::create([
            'category_id' => $skincareCategory->id,
            'name' => 'Mist / Toner',
            'name_kh' => 'ទឹកបាញ់មុខ / ថូណឺ',
            'img_url' => 'https://example.com',
            'created_by' => 1, 'updated_by' => 1,
        ]);

        $cocktails = [
            ['name' => 'HEALING COCKTAIL GREEN', 'kh' => 'សំរាប់ស្បែកស្ងួត រលាក ធ្លាប់ក្រហម', 'price' => 58.00, 'unit_value' => 70, 'unit_name' => 'ml', 'unit_name_kh' => 'ម.ល'],
            ['name' => 'HEALING COCKTAIL YELLOW', 'kh' => 'សំរាប់ស្បែកមានស្នាម ជាំ អុជខ្មៅ អាប់អួរ', 'price' => 58.00, 'unit_value' => 70, 'unit_name' => 'ml', 'unit_name_kh' => 'ម.ល'],
            ['name' => 'HEALING COCKTAIL RED', 'kh' => 'សំរាប់ស្បែកមានស្នាមជ្រួញ យារធ្លាក់', 'price' => 58.00, 'unit_value' => 70, 'unit_name' => 'ml', 'unit_name_kh' => 'ម.ល'],
            ['name' => 'HEALING COCKTAIL BLUE', 'kh' => 'សំរាប់ស្បែកមុន រោល', 'price' => 58.00, 'unit_value' => 70, 'unit_name' => 'ml', 'unit_name_kh' => 'ម.ល']
        ];
        foreach ($cocktails as $cocktail) {
            $p = Product::create([
                'product_type_id' => $tonerType->id,
                'name' => $cocktail['name'],
                'name_kh' => $cocktail['kh'],
                'price' => $cocktail['price'],
                'unit_value' => $cocktail['unit_value'],
                'unit_name' => $cocktail['unit_name'],
                'unit_name_kh' => $cocktail['unit_name_kh'],
                'created_by' => 2, 'updated_by' => 2,
            ]);
            ProductOption::create(['product_id' => $p->id, 'option_type' => 'bigger with promotion','option_type_kh' => 'ប្រម៉ូសិន', 'option_name' => '1+1 (300ml)', 'option_name_kh' => '១+១​ (៣០០ម.ល)', 'price' => 60.00,'created_by' => 2, 'updated_by' => 2]);
            ProductOption::create(['product_id' => $p->id, 'option_type' => 'bigger','option_type_kh' => 'ធំ', 'option_name' => '300ml', 'option_name_kh' => '៣០០ម.ល', 'price' => 105.00, 'created_by' => 2, 'updated_by' => 2]);
            ProductOption::create(['product_id' => $p->id, 'option_type' => 'bigger with promotion','option_type_kh' => 'ធំ ប្រម៉ូសិន', 'option_name' => '1+1 (300ml)', 'option_name_kh' => '១+១​ (៣០០ម.ល)', 'price' => 120.00, 'created_by' => 2, 'updated_by' => 2]);
            ProductOption::create(['product_id' => $p->id, 'option_type' => 'bigger with promotion','option_type_kh' => 'ធំ ប្រម៉ូសិន', 'option_name' => '1+1 (300ml)', 'option_name_kh' => '១+១​ (៣០០ម.ល)', 'price' => 50.00, 'created_by' => 2, 'updated_by' => 2]);
        }

        $tocToner = Product::create([
            'product_type_id' => $tonerType->id,
            'name' => 'ACSEN TOC TONER',
            'name_kh' => 'សំរាប់ស្បែកមុន ងាយប្រតិកម្ម',
            'price' => 44.00,
            'unit_value' => 100,
            'unit_name' => 'ml',
            'unit_name_kh' => 'ម.ល',
            'created_by' => 2, 'updated_by' => 2,
        ]);
        ProductOption::create(['product_id' => $tocToner->id, 'option_type' => 'promotion','option_type_kh' => 'ប្រម៉ូសិន', 'option_name' => '1+1 (100ml)', 'option_name_kh' => '១+១​ (១០០ម.ល)', 'price' => 60.00, 'created_by' => 2, 'updated_by' => 2]);

        $cicaToner = Product::create([
            'product_type_id' => $tonerType->id,
            'name' => 'ACSEN CICA SEN TONER',
            'name_kh' => 'សំរាប់ស្បែកធ្លាប់ក្រហម ប្រតិកម្មខ្លាំង',
            'price' => 47.00,
            'unit_value' => 150,
            'unit_name' => 'ml',
            'unit_name_kh' => 'ម.ល',
            'created_by' => 2, 'updated_by' => 2,
        ]);
        // ProductOption::create(['product_id' => $cicaToner->id, 'size' => '150ml', 'price' => 47.00]);


        // ==========================================
        // STEP 3: SERUM / AMPOULE
        // ==========================================
        $serumType = ProductType::create([
            'category_id' => $skincareCategory->id,
            'name' => 'Serum / Ampoule',
            'name_kh' => 'សេរ៉ូម',
            'img_url' => 'https://example.com',
            'created_by' => 1, 'updated_by' => 1,
        ]);

        $ampoules = [
            ['name' => 'MOISTURIZING AMPOULE', 'kh' => 'សំរាប់ស្បែកស្ងួត រលាក ធ្លាប់ក្រហម', 'price' => 63.00, 'unit_value' => 20, 'unit_name' => 'ml', 'unit_name_kh' => 'ម.ល'],
            ['name' => 'RADIANCE AMPOULE', 'kh' => 'សំរាប់ស្បែកមានស្នាម ជាំ អុជខ្មៅ អាប់អួរ', 'price' => 63.00, 'unit_value' => 20, 'unit_name' => 'ml', 'unit_name_kh' => 'ម.ល'],
            ['name' => 'REJUVENATING AMPOULE', 'kh' => 'សំរាប់ស្បែកមានស្នាមជ្រួញ យារធ្លាក់', 'price' => 63.00, 'unit_value' => 20, 'unit_name' => 'ml', 'unit_name_kh' => 'ម.ល'],
            ['name' => 'PURIFYING AMPOULE', 'kh' => 'សំរាប់ស្បែកមុន រោល', 'price' => 63.00, 'unit_value' => 20, 'unit_name' => 'ml', 'unit_name_kh' => 'ម.ល'],
            ['name' => 'ACSEN SELEMIX SERUM', 'kh' => 'សំរាប់ស្បែកងាយប្រតិកម្ម រោល រន្ធញើសធំ', 'price' => 65.00,  'unit_value' => 40, 'unit_name' => 'ml', 'unit_name_kh' => 'ម.ល'],
            ['name' => 'ACSEN AC CLEAR AMPOULE', 'kh' => 'សំរាប់ស្បែកមុន ងាយប្រតិកម្ម', 'price' => 33.00, 'unit_value' => 7, 'unit_name' => 'ml', 'unit_name_kh' => 'ម.ល'],
            ['name' => 'ACSEN SEN AMPOULE', 'kh' => 'សំរាប់ស្បែកធ្លាប់ក្រហម ប្រតិកម្មខ្លាំង', 'price' => 158.00, 'unit_value' => 35, 'unit_name' => 'ml', 'unit_name_kh' => 'ម.ល'],
            ['name' => 'VITA TONING AMPOULE SERUM', 'kh' => 'សំរាប់ស្បែកស្រអាប់ ជាំ អុជខ្មៅ', 'price' => 145.00, 'unit_value' => 35, 'unit_name' => 'ml', 'unit_name_kh' => 'ម.ល'],
            ['name' => 'RED DE-AGING AMPOULE SERUM', 'kh' => 'សំរាប់ស្បែកជ្រួញយារធ្លាក់ មិនតឹងណែន', 'price' => 189.00, 'unit_value' => 35, 'unit_name' => 'ml', 'unit_name_kh' => 'ម.ល'],
            ['name' => 'MELA-SONIC C-INFUSER', 'kh' => 'សំរាប់ស្បែកស្រអាប់ ជាំ អុជខ្មៅ ពណ៌អត់ស្មើគ្នា', 'price' => 220.00, 'unit_value' => 8, 'unit_name' => 'ml*4', 'unit_name_kh' => 'ម.ល'],
        ];
        foreach ($ampoules as $amp) {
            $p = Product::create([
                'product_type_id' => $serumType->id,
                'name' => $amp['name'],
                'name_kh' => $amp['kh'],
                'price' => $amp['price'],
                'unit_value' => $amp['unit_value'],
                'unit_name' => $amp['unit_name'],
                'unit_name_kh' => $amp['unit_name_kh'],
                'created_by' => 2, 'updated_by' => 2,
            ]);
            // ProductOption::create(['product_id' => $p->id, ]);
        }


        // ==========================================
        // STEP 4: CREAM
        // ==========================================
        $creamType = ProductType::create([
            'category_id' => $skincareCategory->id,
            'name' => 'Cream',
            'name_kh' => 'គ្រីម',
            'img_url' => 'https://example.com',
            'created_by' => 1, 'updated_by' => 1,
        ]);

        $energyCream = Product::create([
            'product_type_id' => $creamType->id,
            'name' => 'ENERGY CREAM',
            'name_kh' => 'សំរាប់ស្បែកស្ងួត ជ្រួញយារធ្លាក់',
            'price' => 72.00,
            'unit_value' => 125,
            'unit_name' => 'ml',
            'unit_name_kh' => 'ម.ល',
            'created_by' => 2, 'updated_by' => 2,
        ]);
        // ProductOption::create(['product_id' => $energyCream->id, 'size' => '125ml', 'price' => 72.00]);

        $recoveryCream = Product::create([
            'product_type_id' => $creamType->id,
            'name' => 'ACSEN RECOVERY CREAM',
            'name_kh' => 'សំរាប់ស្បែកស្តើង សរសៃក្រហម របក',
            'price' => 69.00,
            'unit_value' => 25,
            'unit_name' => 'ml',
            'unit_name_kh' => 'ម.ល',
            'created_by' => 2, 'updated_by' => 2,
        ]);
        ProductOption::create(['product_id' => $recoveryCream->id, 'option_type' => 'promotion','option_type_kh' => 'ប្រូម៉ូសិន', 'option_name' => '25ml (1+1)', 'option_name_kh' => '២៥មល​​ ​(១+១)', 'price' => 69.00, 'created_by' => 2, 'updated_by' => 2]);
        ProductOption::create(['product_id' => $recoveryCream->id, 'option_type' => 'Bigger','option_type_kh' => 'ធំ', 'option_name' => '130ml', 'option_name_kh' => '130មល', 'price' => 140.00, 'created_by' => 2, 'updated_by' => 2]);
        ProductOption::create(['product_id' => $recoveryCream->id, 'option_type' => 'Bigger','option_type_kh' => 'ធំ', 'option_name' => '130ml(1+1)', 'option_name_kh' => '130មល(១+១)', 'price' => 147.00, 'created_by' => 2, 'updated_by' => 2]);

        $acCream = Product::create([
            'product_type_id' => $creamType->id,
            'name' => 'ACSEN AC CREAM',
            'name_kh' => 'សំរាប់ស្បែកខ្ទុះ ស្បែកមុខមុន',
            'price' => 61.00,
            'unit_value' => 25,
            'unit_name' => 'ml',
            'unit_name_kh' => 'ម.ល',
            'created_by' => 2, 'updated_by' => 2,
        ]);
        ProductOption::create(['product_id' => $acCream->id, 'option_type' => 'Promotion','option_type_kh' => 'ប្រូម៉ូសិន', 'option_name' => '25ml (2+1)', 'option_name_kh' => '២៥មល (២+១)', 'price' => 122.00, 'created_by' => 2, 'updated_by' => 2]);

        $shieldCream = Product::create([
            'product_type_id' => $creamType->id,
            'name' => 'SHIELD CREAM',
            'name_kh' => 'សំរាប់ស្បែកខ្សោយខ្លាំង ងាយរងគ្រោះ',
            'price' => 44.00,
            'unit_value' => 25,
            'unit_name' => 'ml',
            'unit_name_kh' => 'ម.ល',
            'created_by' => 2, 'updated_by' => 2,
        ]);
        // ProductOption::create(['product_id' => $shieldCream->id, 'size' => '25ml', 'price' => 44.00]);

        $agtCream = Product::create([
            'product_type_id' => $creamType->id,
            'name' => 'AGT HYDRO CREAM',
            'name_kh' => 'សំរាប់ស្បែកធម្មតា ស្បែកខ្វះទឹក',
            'price' => 138.00,
            'unit_value' => 80,
            'unit_name' => 'ml',
            'unit_name_kh' => 'ម.ល',
            'created_by' => 2, 'updated_by' => 2,
        ]);
        // ProductOption::create(['product_id' => $agtCream->id, 'size' => '80ml', 'price' => 138.00]);

        $vitaCream = Product::create([
            'product_type_id' => $creamType->id,
            'name' => 'VITA TONING AMPOULE CREAM',
            'name_kh' => 'សំរាប់ស្បែកស្រអាប់ ជាំ អុជខ្មៅ',
            'price' => 126.00,
            'unit_value' => 50,
            'unit_name' => 'ml',
            'unit_name_kh' => 'ម.ល',
            'created_by' => 2, 'updated_by' => 2,
        ]);
        // ProductOption::create(['product_id' => $vitaCream->id, 'size' => '50ml', 'price' => 126.00]);

        $redCream = Product::create([
            'product_type_id' => $creamType->id,
            'name' => 'RED DE-AGING AMPOULE CREAM',
            'name_kh' => 'សំរាប់ស្បែកជ្រួញយារធ្លាក់ មិនតឹងណែន',
            'price' => 158.00,
            'unit_value' => 50,
            'unit_name' => 'ml',
            'unit_name_kh' => 'ម.ល',
            'created_by' => 2, 'updated_by' => 2,
        ]);

        // ProductOption::create([
        //     'product_id' => $redCream->id, 
        //     'size' => '50ml',
        //     'price' => 158.00
        // ]);
        // ==========================================// STEP 5: UV PROTECTOR// ==========================================
        $uvType = ProductType::create([
            'category_id' => $skincareCategory->id,
            'name' => 'UV Protector',
            'name_kh' => 'ការពារកម្តៅថ្ងៃ',
            'img_url' => 'example.com',
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        $intenseUv = Product::create([
            'product_type_id' => $uvType->id,
            'name' => 'INTENSE UV PROTECTOR CREAM',
            'name_kh' => 'សំរាប់ស្បែកស្ងួត ជ្រួញ ងាយរងគ្រោះ',
            'price' => 58.00,
            'unit_value' => 50,
            'unit_name' => 'ml',
            'unit_name_kh' => 'ម.ល',
            'created_by' => 2,
            'updated_by' => 2,
            ]);
        ProductOption::create([
            'product_id' => $intenseUv->id,
            'option_type' => 'Promotion','option_type_kh' => 'ប្រូម៉ូសិន', 'option_name' => '50ml (2+1)', 'option_name_kh' => '៥០មល (២+១)',
            'price' => 122.00, 'created_by' => 2, 'updated_by' => 2
        ]);
        $acsenUv = Product::create([
            'product_type_id' => $uvType->id,
            'name' => 'ACSEN UV PROTECTOR ESSENCE',
            'name_kh' => 'សំរាប់ស្បែកមុខមុន រោល ងាយប្រតិកម្ម',
            'price' => 58.00,
            'unit_value' => 50,
            'unit_name' => 'ml',
            'unit_name_kh' => 'ម.ល',
            'created_by' => 2, 'updated_by' => 2,
        ]);
        ProductOption::create([
            'product_id' => $acsenUv->id,
            'option_type' => 'Promotion','option_type_kh' => 'ប្រូម៉ូសិន', 'option_name' => '50ml (2+1)', 'option_name_kh' => '៥០មល (២+១)',
            'price' => 122.00, 'created_by' => 2, 'updated_by' => 2
        ]);
        // ==========================================// STEP 6: BB CREAM / CUSHION// ==========================================
        $bbType = ProductType::create([
            'category_id' => $skincareCategory->id,
            'name' => 'BB Cream / Cushion',
            'name_kh' => 'ម្សៅទ្រនាប់',
            'img_url' => 'example.com',
            'created_by' => 1, 
            'updated_by' => 1,
        ]);
        $bbH = Product::create([
            'product_type_id' => $bbType->id,
            'name' => 'AESTHETIC BB CREAM H+ FORMULA',
            'name_kh' => 'សំរាប់ស្បែកស្ងួត ងាយប្រតិកម្ម',
            'price' => 44.00,
            'unit_value' => 15,
            'unit_name' => 'ml',
            'unit_name_kh' => 'ម.ល',
            'created_by' => 2, 
            'updated_by' => 2,
        ]);
        // ProductOption::create([
        //     'product_id' => $bbH->id, 
        //     'size' => '15ml', 
        //     'price' => 44.00
        // ]);
        $bbA = Product::create([
            'product_type_id' => $bbType->id,
            'name' => 'AESTHETIC BB CREAM A+ FORMULA',
            'name_kh' => 'សំរាប់ស្បែកខ្លាញ់ មានមុន',
            'price' => 44.00,
            'unit_value' => 15,
            'unit_name' => 'ml',
            'unit_name_kh' => 'ម.ល',
            'created_by' => 2, 'updated_by' => 2,
        ]);
        // ProductOption::create([
        //     'product_id' => $bbA->id, 
        //     'size' => '15ml', 
        //     'price' => 44.00
        // ]);
        $cushion = Product::create([
            'product_type_id' => $bbType->id,
            'name' => 'TROIAREUKE SEOUL CUSHION',
            'name_kh' => 'សំរាប់គ្រប់ប្រភេទស្បែក',
            'price' => 58.00,
            'unit_value' => 15,
            'unit_name' => 'ml',
            'unit_name_kh' => 'ម.ល',
            'created_by' => 2, 
            'updated_by' => 2,
        ]);
        // ProductOption::create([
        //     'product_id' => $cushion->id,
        //     'size' => '15ml', 
        //     'price' => 58.00
        // ]);
        // ==========================================// EYE CARE// ==========================================
        $eyeType = ProductType::create([
            'category_id' => $skincareCategory->id,
            'name' => 'Eye Care',
            'name_kh' => 'ផលិតផលថែរក្សាស្បែកជុំវិញភ្នែក',
            'img_url' => 'example.com',
            'created_by' => 1, 
            'updated_by' => 1,
        ]);
        $eyePatch = Product::create([
            'product_type_id' => $eyeType->id,
            'name' => 'ANTI-WRINKLE COLLAGEN EYE PATCH',
            'name_kh' => 'សំរាប់ស្បែកជ្រួញ យារធ្លាក់ ស្លក់',
            'price' => 40.00,
            'unit_value' => 60,
            'unit_name' => 'pcs',
            'unit_name_kh' => 'ដុំ',
            'created_by' => 2, 
            'updated_by' => 2,
        ]);
        ProductOption::create([
            'product_id' => $eyePatch->id,
            'option_type' => 'Promotion','option_type_kh' => 'ប្រូម៉ូសិន', 'option_name' => '60pcs (2+1)', 'option_name_kh' => '៦០ដុំ (២+១)',
            'price' => 82.00, 'created_by' => 2, 'updated_by' => 2
        ]);
        $eyeCream = Product::create([
            'product_type_id' => $eyeType->id,
            'name' => 'ANTI-WRINKLE EYE CREAM',
            'name_kh' => 'សំរាប់ស្បែកជ្រួញ យារធ្លាក់ ស្លក់',
            'price' => 143.00,
            'unit_value' => 25,
            'unit_name' => 'ml',
            'unit_name_kh' => 'ម.ល',
            'created_by' => 2, 
            'updated_by' => 2,
        ]);
        ProductOption::create([
            'product_id' => $eyeCream->id,
            'option_type' => 'Promotion','option_type_kh' => 'ប្រូម៉ូសិន', 'option_name' => '25ml (1+1)', 'option_name_kh' => '២៥មល (១+១)', 
            'price' => 143.00, 'created_by' => 2, 'updated_by' => 2
        ]);
        // ==========================================// EXFOLIATION// ==========================================
        $exfoliationType = ProductType::create([
            'category_id' => $skincareCategory->id,
            'name' => 'Exfoliation',
            'name_kh' => 'ផលិតផលជម្រុះកោសិកាចាស់',
            'img_url' => 'example.com',
            'created_by' => 1, 
            'updated_by' => 1,
        ]);
        $scaling = Product::create([
            'product_type_id' => $exfoliationType->id,
            'name' => 'A-PREP SKIN SCALING',
            'name_kh' => 'សំរាប់គ្រប់ប្រភេទស្បែក',
            'price' => 84.00,
            'unit_value' => 30,
            'unit_name' => 'ml',
            'unit_name_kh' => 'ម.ល',
            'created_by' => 2, 
            'updated_by' => 2,
        ]);
        // ProductOption::create([
        //     'product_id' => $scaling->id, 
        //     'size' => '30ml', 
        //     'price' => 84.00
        // ]);
        $bufferPad = Product::create([
            'product_type_id' => $exfoliationType->id,
            'name' => 'A-PREP BUFFERING PAD - SKIN BOOSTER',
            'name_kh' => 'សំរាប់គ្រប់ប្រភេទស្បែក',
            'price' => 98.00,
            'unit_value' => 30,
            'unit_name' => 'pcs',
            'unit_name_kh' => 'ដុំ',
            'created_by' => 2,
            'updated_by' => 2,
        ]);
        // ProductOption::create([
        //     'product_id' => $bufferPad->id, 
        //     'size' => '30pcs', 
        //     'price' => 98.00
        // ]);
        // ==========================================// SPECIAL CARE// ==========================================
        $specialType = ProductType::create([
            'category_id' => $skincareCategory->id,
            'name' => 'Special Care',
            'name_kh' => 'ផលិតផលបំប៉នបន្ថែម',
            'img_url' => 'example.com',
            'created_by' => 1, 
            'updated_by' => 1,
        ]);
        $agtEssence = Product::create([
            'product_type_id' => $specialType->id,
            'name' => 'A.G.T HYDRO ESSENCE',
            'name_kh' => 'សំរាប់គ្រប់ប្រភេទស្បែក',
            'price' => 55.00,
            'unit_value' => 100,
            'unit_name' => 'ml',
            'unit_name_kh' => 'ម.ល',
            'created_by' => 2, 
            'updated_by' => 2,
        ]);
        // ProductOption::create([
        //     'product_id' => $agtEssence->id, 
        //     'size' => '100ml', 
        //     'price' => 55.00
        // ]);
        ProductOption::create([
            'product_id' => $agtEssence->id,
            'option_type' => 'Bigger','option_type_kh' => 'ធំ', 'option_name' => '200ml', 'option_name_kh' => '200មល',
            'price' => 85.00, 'created_by' => 2, 'updated_by' => 2
        ]);
        $specialItems = [
            [
                'name' => 'ACSEN SOS SLEEPING MASK', 
                'kh' => 'សំរាប់ស្បែកខូច ខូចរបាំងការពារ ងាយក្រហម', 
                'unit_value' => 50,
                'unit_name' => 'ml',
                'unit_name_kh' => 'ម.ល', 
                'price' => 45.00
            ],
            [
                'name' => 'ACSEN AC SPOT SOLUTION', 
                'kh' => 'សំរាប់ស្បែកមានកំបុត មុនខ្ទុះ',
                'unit_value' => 15,
                'unit_name' => 'ml',
                'unit_name_kh' => 'ម.ល',
                'price' => 66.00
            ],
            [
                'name' => 'TONING SERUM MASK',
                'kh' => 'សំរាប់ស្បែកជាំ រលាកក្រហម ស្នាមខ្មៅ ជ្រួញ', 
                'unit_value' => 100,
                'unit_name' => 'pcs',
                'unit_name_kh' => 'ដុំ',
                'price' => 60.00
            ],
            [
                'name' => 'ACSEN PORE CONTROL MASK', 
                'kh' => 'សំរាប់ស្បែកមុន ងាយប្រតិកម្ម រន្ធញើសធំ', 
                'unit_value' => 50,
                'unit_name' => 'ml',
                'unit_name_kh' => 'ម.ល', 
                'price' => 44.00
            ],
            [
                'name' => 'GPS MASK RED DE-AGING', 
                'kh' => 'សំរាប់ស្បែកជ្រួញយារធ្លាក់ មិនតឹងណែន',
                'unit_value' => 1,
                'unit_name' => 'ea',
                'unit_name_kh' => 'ដុំ',
                'price' => 80.00
            ],
            [
                'name' => 'GPS MASK VITA TONING 2-STEP',
                'kh' => 'សំរាប់ស្បែកជ្រួញយារធ្លាក់ មិនតឹងណែន', 
                'unit_value' => 1,
                'unit_name' => 'ea',
                'unit_name_kh' => 'ដុំ',
                'price' => 90.00
            ],
            [
                'name' => 'GPS MASK T-RESCUE', 
                'kh' => 'សំរាប់ស្បែកជ្រួញយារធ្លាក់ មិនតឹងណែន', 
                'unit_value' => 1,
                'unit_name' => 'ea',
                'unit_name_kh' => 'ដុំ',
                'price' => 80.00
            ],
            [
                'name' => 'VVS MASK', 
                'kh' => 'សំរាប់ស្បែកខូច ជាំអុជខ្មៅ រន្ធញើសធំ', 
                'unit_value' => 1,
                'unit_name' => 'ea',
                'unit_name_kh' => 'ដុំ', 
                'price' => 85.00
            ],
            [
                'name' => 'ACSEN PURPLE CICA MASK', 
                'kh' => 'ស្បែកងាយប្រតិកម្ម មុន ស្បែករលាកទៅងើកខ្មៅ', 
                'unit_value' => 1,
                'unit_name' => 'ea',
                'unit_name_kh' => 'ដុំ', 
                'price' => 90.00
            ],
        ];
        foreach ($specialItems as $item) {
            $p = Product::create([
                'product_type_id' => $specialType->id,
                'name' => $item['name'],
                'name_kh' => $item['kh'],
                'unit_value' => $item['unit_value'],
                'unit_name' => $item['unit_name'],
                'unit_name_kh' => $item['unit_name_kh'],
                'price' => $item['price'],
                'created_by' => 2, 
                'updated_by' => 2,
            ]);
            // ProductOption::create([
            //     'product_id' => $p->id, 
            //     'size' => $item['size'], 
            //     'price' => $item['price']
            // ]);
        }
        

        // seed the second store's product //
         
        // 1. Seed Parent Product Category
        $beveragesCategory = ProductCategory::create([
            'parent_id' => null,
            'name' => 'Food & Beverages',
            'name_kh' => 'អាហារ និងភេសជ្ជៈ',
            'slug' => 'food-and-beverages',
            'description' => 'Delicious refreshments and local snacks.',
            'description_kh' => 'ភេសជ្ជៈស្រស់ស្រាយ និងអាហារសម្រន់ក្នុងស្រុក។',
            'image_url' => null,
            'icon' => 'mug-saucer',
            'is_active' => true,
            'display_order' => 2,
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
            'created_by' => 5, // Admin Store Manager footprint
            'updated_by' => 5,
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
            'created_by' => 5,
            'updated_by' => 5,
        ]);

        ProductOption::create([
            'product_id' => $coffeeProduct->id,
            'option_type' => 'Size',
            'option_type_kh' => 'ទំហំ',
            'option_name' => 'Large',
            'option_name_kh' => 'ធំ',
            'price' => 0.50, // Extra $0.50 charge
            'image_url' => null,
            'created_by' => 5,
            'updated_by' => 5,
        ]);

        // Gallery Images for Coffee Product
        ProductImage::create([
            'product_id' => $coffeeProduct->id,
            'image_url' => 'https://example.com',
            'is_primary' => true,
            'created_by' => 5,
            'updated_by' => 5,
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
            'created_by' => 5,
            'updated_by' => 5,
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
            'created_by' => 5,
            'updated_by' => 5,
        ]);

        ProductImage::create([
            'product_id' => $sodaProduct->id,
            'image_url' => 'https://example.com',
            'is_primary' => true,
            'created_by' => 5,
            'updated_by' => 5,
        ]);
    }
}