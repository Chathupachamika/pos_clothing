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
        Schema::create('products', function (Blueprint $table) {
            $table->id('id');
            // $table->foreignId('inventory_id')->constrained('inventories')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('admin_id')->constrained('admins');
            $table->string('size')->nullable();
            $table->string('color')->nullable();
            $table->text('description');
            $table->string('category');
            $table->string('location')->nullable();
            // $table->string('status');
            $table->string('name');
            $table->string('bar_code')->nullable()->unique();
            $table->string('brand_name');
            $table->decimal('selling_price', 10, 2)->nullable();
            $table->decimal('discount', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
