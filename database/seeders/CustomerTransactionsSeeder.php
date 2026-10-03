<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Favorite;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\Status;
use Illuminate\Database\Seeder;

class CustomerTransactionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch valid dynamic references from your previously executed seeders
        $customerId = 4; // John Customer from UserSeeder
        $cashierId = 3;  // Bopa Cashier from UserSeeder
        
        $latteProduct = Product::where('name', 'Iced Latte')->first();
        $cokeProduct = Product::where('name', 'Coca-Cola Can')->first();
        
        $latteOption = ProductOption::where('product_id', $latteProduct->id)->where('option_name', 'Large')->first();
        $cokeOption = ProductOption::where('product_id', $cokeProduct->id)->first();

        // 1. Seed the Addresses Table
        $address = Address::create([
            'title' => 'Home Villa',
            'address' => 'Street 2004, Phnom Penh, Cambodia',
            'address_kh' => 'ផ្លូវ ២០០៤, ភ្នំពេញ, កម្ពុជា',
            'contact' => '+85512345678',
            'lat' => 11.55640000000000,
            'long' => 104.928200000000,
            'is_default' => true,
            'created_by' => $customerId,
            'updated_by' => $customerId,
        ]);

        // 2. Seed the Favorites Table
        Favorite::create([
            'user_id' => $customerId,
            'product_id' => $latteProduct->id,
        ]);

        // 3. Seed the Carts & Cart Items Table (Simulating an active shopper session)
        $cart = Cart::create([
            'expires_at' => now()->addDays(7),
            'created_by' => $customerId,
            'updated_by' => $customerId,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $cokeProduct->id,
            'product_option_id' => $cokeOption->id,
            'quantity' => 3,
            'price' => $cokeProduct->price,
            'created_by' => $customerId,
            'updated_by' => $customerId,
        ]);

        // 4. Seed the Orders & Order Details Table (Simulating a historical checkout)
        $orderPendingStatus = Status::where('type', 'order')->where('value', 'pending')->first();
        
        $order = Order::create([
            'address_id' => $address->id,
            'status_id' => $orderPendingStatus ? $orderPendingStatus->id : null,
            'total_amount' => 3.00, // (1 x $2.50 base price) + $0.50 large cup variant option charge
            'receipt_number' => 'REC-INIT-SEED-' . time(),
            'date' => now()->format('Y-m-d'),
            'created_by' => $customerId,
            'updated_by' => $customerId,
        ]);

        OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $latteProduct->id,
            'product_option_id' => $latteOption->id,
            'quantity' => 1,
            'price' => 3.00,
        ]);

        // 5. Seed the Payments Table (Simulating an offline counter service collection)
        $paymentPaidStatus = Status::where('type', 'payment')->where('value', 'paid')->first();

        Payment::create([
            'order_id' => $order->id,
            'transaction_id' => 'counter_tx_seeder_init',
            'status_id' => $paymentPaidStatus ? $paymentPaidStatus->id : null,
            'fulfillment_type' => 'pickup',
            'method' => 'pay_at_counter',
            'created_by' => $cashierId, // Processed directly by the cashier profile
            'updated_by' => $cashierId,
        ]);
        
        // Mark order as processing now that payment is settled
        $orderProcessingStatus = Status::where('type', 'order')->where('value', 'processing')->first();
        if ($orderProcessingStatus) {
            $order->update(['status_id' => $orderProcessingStatus->id]);
        }
    }
}
