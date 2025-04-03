<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('grn_notes', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number')->unique();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->foreignId('admin_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->json('product_details');
            $table->date('received_date');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('grn_notes');
    }
};