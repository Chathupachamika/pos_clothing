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
        // Create orders table
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->dateTime('time');
            $table->boolean('status');
            $table->enum('payment_type', ['CASH', 'CREDIT_CARD', 'DEBIT_CARD']);
            $table->double('amount');
            $table->double('discount');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });

        // Create order_items table
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('bar_code');
            $table->integer('quantity');
            $table->double('price');
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
