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
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_type'); // daily, weekly, monthly, yearly
            $table->string('report_name'); // best_selling_products, low_stock_alerts, inventory_summary
            $table->longText('report_data'); // JSON data of the report
            $table->timestamp('report_period_start')->nullable();
            $table->timestamp('report_period_end')->nullable();
            $table->string('generated_by')->nullable(); // user who generated the report
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_reports');
    }
};