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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('address_id')->nullable()->constrained('addresses')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('status_id')->nullable()->constrained('statuses')->onDelete('cascade');
            $table->decimal('total_amount', 10, 2);
            // $table->enum('status', ['Pending', 'Processing', 'Shipped', 'Delivered'])->default('Pending');
            $table->string('receipt_number')->unique();
            // $table->foreignId('status_id')->nullable()->constrained('statuses');
            $table->date('date')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreignId('updated_by')->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
