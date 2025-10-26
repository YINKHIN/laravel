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
        Schema::table('products', function (Blueprint $table) {
            // Check if batch_number column exists
            if (!Schema::hasColumn('products', 'batch_number')) {
                $table->string('batch_number', 50)->nullable()->after('reorder_quantity');
            }
            
            // Check if expiration_date column exists
            if (!Schema::hasColumn('products', 'expiration_date')) {
                $table->date('expiration_date')->nullable()->after('batch_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'batch_number')) {
                $table->dropColumn('batch_number');
            }
            
            if (Schema::hasColumn('products', 'expiration_date')) {
                $table->dropColumn('expiration_date');
            }
        });
    }
};