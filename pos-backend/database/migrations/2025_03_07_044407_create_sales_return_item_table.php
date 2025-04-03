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
        Schema::create('sales_return_item', function (Blueprint $table) {
            $table->unsignedBigInteger('return_item_id');
            $table->unsignedBigInteger('sales_id');
            $table->time('returned_at');
            // $table->primaryKey(['sales_id', 'return_item_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_return_item');
    }
};
