<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('grn_notes', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_id')->nullable()->change();
            $table->unsignedBigInteger('admin_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('grn_notes', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_id')->nullable(false)->change();
            $table->unsignedBigInteger('admin_id')->nullable(false)->change();
        });
    }
};
