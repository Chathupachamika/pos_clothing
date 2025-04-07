<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesReturnItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_item_id');
            $table->integer('quantity');
            $table->string('reason');
            $table->timestamps();

            $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('cascade');
        });

        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('barcode');
            $table->timestamp('returned_at');
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('barcode')->references('barcode')->on('product_variations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales_return_items');
        Schema::dropIfExists('return_items');
    }
}
