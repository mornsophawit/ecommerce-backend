<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('product_option_id')->constrained('product_options')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->float('price', 10, 2);
            $table->timestamp('created_at')->useCurrent();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreignId('updated_by')->constrained('users');
        });

        // Schema::create('cart_item_product_option', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('cart_item_id')->constrained('cart_items')->onDelete('cascade');
        //     $table->foreignId('product_option_id')->constrained('product_options')->onDelete('cascade');
        //     $table->unique(['cart_item_id', 'product_option_id'], 'ci_po_unique');
        //     $table->timestamps();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_item_product_option');
        Schema::dropIfExists('cart_items');
    }
};