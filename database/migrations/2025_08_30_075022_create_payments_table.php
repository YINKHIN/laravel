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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->dateTime('pay_date');
            $table->unsignedBigInteger('staff_id');
            $table->string('full_name', 100);
            $table->unsignedBigInteger('ord_code');
            $table->decimal('total', 10, 2);
            $table->decimal('deposit', 10, 2);
            $table->decimal('remain', 10, 2);
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staffs');
            $table->foreign('ord_code')->references('id')->on('orders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
