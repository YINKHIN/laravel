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
        Schema::table('imports', function (Blueprint $table) {
            // Check if supplier column doesn't exist before adding
            if (!Schema::hasColumn('imports', 'supplier')) {
                $table->string('supplier', 100)->nullable()->after('sup_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            if (Schema::hasColumn('imports', 'supplier')) {
                $table->dropColumn('supplier');
            }
        });
    }
};
