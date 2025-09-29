<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyStatusColumnInOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', ['Pending', 'Paid', 'Processing', 'Completed', 'Cancelled'])
                  ->default('Pending')
                  ->change();
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert to previous ENUM values (adjust based on your original schema)
            $table->enum('status', ['pending', 'processing', 'completed'])
                  ->default('pending')
                  ->change();
        });
    }
}