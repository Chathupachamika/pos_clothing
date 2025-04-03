<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('grn_notes', function (Blueprint $table) {
            $table->integer('previous_quantity')->default(0)->after('received_date');
            $table->integer('new_quantity')->default(0)->after('previous_quantity');
            $table->integer('adjusted_quantity')->default(0)->after('new_quantity');
            $table->string('adjustment_type')->default('addition')->after('adjusted_quantity');
        });
    }

    public function down()
    {
        Schema::table('grn_notes', function (Blueprint $table) {
            $table->dropColumn(['previous_quantity', 'new_quantity', 'adjusted_quantity', 'adjustment_type']);
        });
    }
};
