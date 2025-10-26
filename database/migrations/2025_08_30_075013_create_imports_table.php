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
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->date('imp_date');
            $table->unsignedBigInteger('staff_id');
            $table->string('full_name', 100);
            $table->unsignedBigInteger('sup_id');
            $table->string('supplier', 100);
            $table->decimal('total', 10, 2);
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staffs');
            $table->foreign('sup_id')->references('id')->on('suppliers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};