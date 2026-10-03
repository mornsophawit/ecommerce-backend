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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('user_id')->unique()->constrained('users');
            // $table->timestamp('created_at')->useCurrent();
            // $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->timestamp('expires_at')->nullable();
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
        Schema::dropIfExists('carts');
    }
};