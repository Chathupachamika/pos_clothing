<?php

use App\Models\Cashier;
use App\Models\Customer;
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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->dateTime('time');
            $table->boolean('status');
            $table->enum('payment_type', ['CASH', 'CREDIT_CARD', 'DEBIT_CARD']);
            $table->double('amount');
            $table->double('discount');
            $table->foreignIdFor(Cashier::class);
            $table->foreignIdFor(Customer::class)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
