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
        Schema::table('orders', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('orders', 'subtotal')) {
                $table->decimal('subtotal', 10, 2)->default(0.00)->after('total');
            }
            
            if (!Schema::hasColumn('orders', 'tax_percent')) {
                $table->decimal('tax_percent', 5, 2)->default(0.00)->after('tax');
            }
            
            if (!Schema::hasColumn('orders', 'discount_percent')) {
                $table->decimal('discount_percent', 5, 2)->default(0.00)->after('discount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'subtotal')) {
                $table->dropColumn('subtotal');
            }
            
            if (Schema::hasColumn('orders', 'tax_percent')) {
                $table->dropColumn('tax_percent');
            }
            
            if (Schema::hasColumn('orders', 'discount_percent')) {
                $table->dropColumn('discount_percent');
            }
        });
    }
};