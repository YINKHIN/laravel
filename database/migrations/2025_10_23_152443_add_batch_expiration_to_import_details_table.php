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
        Schema::table('import_details', function (Blueprint $table) {
            // Add batch and expiration fields to track per import
            if (!Schema::hasColumn('import_details', 'batch_number')) {
                $table->string('batch_number', 50)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('import_details', 'expiration_date')) {
                $table->date('expiration_date')->nullable()->after('batch_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_details', function (Blueprint $table) {
            if (Schema::hasColumn('import_details', 'batch_number')) {
                $table->dropColumn('batch_number');
            }
            if (Schema::hasColumn('import_details', 'expiration_date')) {
                $table->dropColumn('expiration_date');
            }
        });
    }
};
