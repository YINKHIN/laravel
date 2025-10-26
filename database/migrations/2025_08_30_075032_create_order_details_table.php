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
        Schema::create('order_details', function (Blueprint $table) {
            $table->unsignedBigInteger('ord_code');
            $table->unsignedBigInteger('pro_code');
            $table->string('pro_name', 100);
            $table->smallInteger('qty');
            $table->decimal('price', 10, 2);
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->primary(['ord_code', 'pro_code']);
            $table->foreign('ord_code')->references('id')->on('orders');
            $table->foreign('pro_code')->references('id')->on('products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};
