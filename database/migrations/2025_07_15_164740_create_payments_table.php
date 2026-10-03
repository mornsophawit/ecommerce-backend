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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            // $table->string('status')->default('Pending');
            $table->string('transaction_id')->nullable();
            $table->foreignId('status_id')->nullable()->constrained('statuses');
            $table->enum('fulfillment_type', ['shipping', 'pickup']);
            $table->enum('method', ['cash_on_delivery', 'stripe', 'khqr', 'pay_at_counter', 'aba_payway']);
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
        Schema::dropIfExists('payments');
    }
};
