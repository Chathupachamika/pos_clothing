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
        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('size');
            $table->string('color');
            $table->decimal('price', 10, 2);
            $table->decimal('selling_price', 10, 2);
            $table->integer('quantity')->default(0);
            $table->string('barcode')->nullable();
            $table->decimal('discount', 5, 2)->nullable()->default(0.00);
            $table->enum('status', ['In Stock', 'Low Stock', 'Out of Stock'])->default('In Stock');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variations');
    }
};
