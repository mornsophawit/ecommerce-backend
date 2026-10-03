<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\Branch;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductType;
use App\Models\ProductOption;
use App\Models\Status;
use App\Models\User;
use App\Models\Refund;
use App\Models\RefundItem;
use App\Models\Address;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BranchInventoryLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $admin;
    protected Store $store;
    protected Branch $branch;
    protected ProductCategory $category;
    protected ProductType $productType;
    protected Product $product;
    protected ProductOption $productOption;
    protected Status $pendingOrderStatus;
    protected Status $processingOrderStatus;
    protected Status $pendingPaymentStatus;
    protected Status $paidPaymentStatus;
    protected Status $pendingRefundStatus;
    protected Status $approvedRefundStatus;
    protected int $addressId;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create a Seed Master Admin profile
        $this->admin = User::create([
            'id' => 1,
            'name' => 'Super Admin',
            'email' => 'admin@kroeng.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'created_by' => 1,
            'updated_by' => 1
        ]);

        // 2. Create the Customer profile
        $this->customer = User::create([
            'id' => 2,
            'name' => 'John Doe',
            'email' => 'customer@kroeng.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id
        ]);

        // 3. Setup Parent Store Profile
        $this->store = Store::create([
            'name' => 'Kroeng Market Head Office',
            'name_kh' => 'គ្រឿង ម៉ាត ការិយាល័យកណ្តាល',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id
        ]);

        // 4. Setup Base Branch Infrastructure
        $this->branch = Branch::create([
            'store_id' => $this->store->id,
            'name' => 'Phnom Penh Central',
            'name_kh' => 'ភ្នំពេញ សាខាកណ្តាល',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id
        ]);

        // 5. Build Structural Hierarchy Nodes
        $this->category = ProductCategory::create([
            'name' => 'Soft Drinks',
            'name_kh' => 'ភេសជ្ជៈផ្អែម',
            'slug' => 'soft-drinks',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id
        ]);

        $this->productType = ProductType::create([
            'category_id' => $this->category->id,
            'name' => 'Carbonated Cans',
            'name_kh' => 'ភេសជ្ជៈកំប៉ុងមានហ្គាស',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id
        ]);
        
        // 6. Instantiate Global Product Template by bypassing fillable filtering constraints
        $this->product = new Product();
        $this->product->product_type_id = $this->productType->id;
        $this->product->name            = 'Coca-Cola Can';
        $this->product->name_kh         = 'កូកាកូឡា កំប៉ុង';
        $this->product->price           = 0.60;
        $this->product->stock           = 0;
        $this->product->unit_value      = 330.0000;
        $this->product->unit_name       = 'ml';
        $this->product->unit_name_kh    = 'មីលីលីត្រ';
        $this->product->created_by      = $this->admin->id;
        $this->product->updated_by      = $this->admin->id;
        $this->product->save();

        // 7. Setup Product Option matching your exact model/migration properties
        $this->productOption = ProductOption::create([
            'product_id'     => $this->product->id,
            'option_type'    => 'Size',
            'option_type_kh' => 'ទំហំ',
            'option_name'    => 'Standard Can',
            'option_name_kh' => 'កំប៉ុងស្តង់ដារ',
            'price'          => 0.00, // Fixed syntax typo here: changed = to =>
            'image_url'      => null,
            'created_by'     => $this->admin->id,
            'updated_by'     => $this->admin->id
        ]);


        // 8. Link Catalog Item to Branch with an Initial Pivot Stock Pool
        DB::table('branch_product')->insert([
            'branch_id'  => $this->branch->id,
            'product_id' => $this->product->id,
            'stock'      => 50,
            'price'      => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 9. Setup Dynamic Global Status Parameters
        $this->pendingOrderStatus = Status::create([
            'type' => 'order', 'value' => 'pending', 'name' => 'Pending Order', 'name_kh' => 'ការបញ្ជាទិញរង់ចាំ', 'created_by' => $this->admin->id, 'updated_by' => $this->admin->id
        ]);
        $this->processingOrderStatus = Status::create([
            'type' => 'order', 'value' => 'processing', 'name' => 'Processing Order', 'name_kh' => 'ការបញ្ជាទិញកំពុងដំណើរការ', 'created_by' => $this->admin->id, 'updated_by' => $this->admin->id
        ]);
        $this->pendingPaymentStatus = Status::create([
            'type' => 'payment', 'value' => 'pending', 'name' => 'Payment Pending', 'name_kh' => 'កំពុងរង់ចាំប្រាក់', 'created_by' => $this->admin->id, 'updated_by' => $this->admin->id
        ]);
        $this->paidPaymentStatus = Status::create([
            'type' => 'payment', 'value' => 'paid', 'name' => 'Payment Paid', 'name_kh' => 'បានបង់ប្រាក់', 'created_by' => $this->admin->id, 'updated_by' => $this->admin->id
        ]);
        $this->pendingRefundStatus = Status::create([
            'type' => 'refund', 'value' => 'pending', 'name' => 'Pending Refund', 'name_kh' => 'ការបង្វិលសងរង់ចាំ', 'created_by' => $this->admin->id, 'updated_by' => $this->admin->id
        ]);
        $this->approvedRefundStatus = Status::create([
            'type' => 'refund', 'value' => 'approved', 'name' => 'Approved Refund', 'name_kh' => 'ការបង្វិលសងជោគជ័យ', 'created_by' => $this->admin->id, 'updated_by' => $this->admin->id
        ]);
        
        // 10. Build Mock Address Entry
        $address = Address::create([
            'title' => 'Home',
            'address' => 'St 123, Phnom Penh',
            'contact' => '+85512345678',
            'created_by' => $this->customer->id,
            'updated_by' => $this->customer->id
        ]);
        
        $this->addressId = $address->id;
    }

    /** @test */
    public function it_decrements_branch_stock_on_checkout_processes_payment_and_restores_it_on_refund_approval()
    {
        // --- STAGE 1: INITIATE CART FILL ---
        $cart = Cart::create([
            'created_by' => $this->customer->id,
            'updated_by' => $this->customer->id,
            'expires_at' => now()->addDays(7)
        ]);
        
        $cartItem = new CartItem();
        $cartItem->cart_id           = $cart->id;
        $cartItem->branch_id         = $this->branch->id;
        $cartItem->product_id        = $this->product->id;
        $cartItem->product_option_id = $this->productOption->id;
        $cartItem->quantity          = 5;
        $cartItem->price             = 0.60;
        $cartItem->created_by        = $this->customer->id;
        $cartItem->updated_by        = $this->customer->id;
        $cartItem->save();

         // --- STAGE 2: EXECUTE CHECKOUT VIA ORDER CONTROLLER ---
        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/orders', [
                'address_id'       => $this->addressId,
                'branch_id'        => $this->branch->id,          // Explicit branch matching context
                'fulfillment_type' => 'shipping',                 // Explicit checkout fulfillment target context
                'payment_method'   => 'khqr'                      // Fallback payment parameter framework
            ]);

        $response->assertStatus(201);

        // Verify branch stock drops from 50 to 45 immediately after checkout reservation steps
        $this->assertDatabaseHas('branch_product', [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'stock' => 45,
        ]);

        $orderId = $response->json('data.id');
        $orderDetailId = DB::table('order_details')->where('order_id', $orderId)->first()->id;

        // --- STAGE 3: PROCESS PAYMENT TRANSACTIONS HUB ---
        $paymentResponse = $this->actingAs($this->customer, 'api')
            ->postJson('/api/payments/process', [
                'order_id' => $orderId,
                'fulfillment_type' => 'shipping',
                'method' => 'khqr'
            ]);

        $paymentResponse->assertStatus(201);

        // --- STAGE 4: CUSTOMER SUBMITS REFUND DISPUTE TICKET ---
        $refundResponse = $this->actingAs($this->customer, 'api')
            ->postJson('/api/refunds', [
                'order_id' => $orderId,
                'reason' => 'wrong_item',
                'images' => ['https://example.com'],
                'items' => [
                    [
                        'order_detail_id' => $orderDetailId,
                        'quantity' => 2, 
                    ]
                ]
            ]);

        $refundResponse->assertStatus(201);
        $refundId = $refundResponse->json('data.id');

        // Stock remains 45 because verification is still pending review
        $this->assertDatabaseHas('branch_product', [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'stock' => 45,
        ]);

        // --- STAGE 5: ADMIN APPROVES DISPUTE & RESTORES STOCK ---
        $reviewResponse = $this->actingAs($this->admin, 'api')
            ->postJson("/api/refunds/{$refundId}/review", [
                'action' => 'approve'
            ]);

        $reviewResponse->assertStatus(200);

        // Success: Verify stock correctly increased back to 47 items (45 + 2 returned units)
        $this->assertDatabaseHas('branch_product', [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'stock' => 47,
        ]);
    }
}
