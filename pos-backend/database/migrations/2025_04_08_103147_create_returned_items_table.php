<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('returned_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_variation_id')->constrained('product_variations')->onDelete('cascade');
            $table->integer('quantity');
            $table->text('reason');
            $table->decimal('returned_amount', 10, 2)->default(0.00);
            $table->timestamp('return_date')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('returned_items');
    }
};
