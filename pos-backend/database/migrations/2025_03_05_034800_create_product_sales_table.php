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
        Schema::create('product_sales', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('sales_id');
            $table->double('quantity');
            $table->double('price');
            $table->timestamps();
            $table->primary(['sales_id', 'product_id']);
            // $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            // $table->foreign('sales_id')->references('id')->on('sales')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_sales');
    }
};
