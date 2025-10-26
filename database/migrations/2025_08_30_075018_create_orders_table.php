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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->dateTime('ord_date');
            $table->unsignedBigInteger('staff_id');
            $table->string('full_name', 100);
            $table->unsignedBigInteger('cus_id');
            $table->string('cus_name', 100);
            $table->decimal('total', 10, 2);
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staffs');
            $table->foreign('cus_id')->references('id')->on('customers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
