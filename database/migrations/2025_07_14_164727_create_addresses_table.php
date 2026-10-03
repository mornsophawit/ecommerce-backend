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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('address');
            $table->text('address_kh')->nullable();
            $table->string('contact');
            $table->decimal('lat', 16, 14)->nullable();
            $table->decimal('long', 15, 12)->nullable();
            $table->boolean('is_default')->default(false);
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
        Schema::dropIfExists('addresses');
    }
};
