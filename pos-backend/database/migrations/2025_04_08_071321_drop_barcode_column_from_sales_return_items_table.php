<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_return_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_return_items', 'return_item_id')) {
                $table->unsignedBigInteger('return_item_id')->after('order_id');
                $table->foreign('return_item_id')->references('id')->on('return_items')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_return_items', function (Blueprint $table) {
            if (Schema::hasColumn('sales_return_items', 'return_item_id')) {
                $table->dropForeign(['return_item_id']);
                $table->dropColumn('return_item_id');
            }
        });
    }
};
