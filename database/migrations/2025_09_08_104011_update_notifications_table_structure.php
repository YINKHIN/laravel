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
        // Update notifications table to match the actual database structure
        Schema::table('notifications', function (Blueprint $table) {
            // Check if columns exist before adding them
            if (!Schema::hasColumn('notifications', 'notifiable_type')) {
                $table->string('notifiable_type', 255)->after('type');
            }
            
            if (!Schema::hasColumn('notifications', 'notifiable_id')) {
                $table->unsignedBigInteger('notifiable_id')->after('notifiable_type');
            }
            
            if (!Schema::hasColumn('notifications', 'data')) {
                $table->text('data')->after('notifiable_id');
            }
            
            if (!Schema::hasColumn('notifications', 'read_at')) {
                $table->timestamp('read_at')->nullable()->after('data');
            }
            
            // Add indexes if they don't exist
            if (!Schema::hasIndex('notifications', 'notifications_notifiable_type_notifiable_id_index')) {
                $table->index(['notifiable_type', 'notifiable_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Check if columns exist before dropping them
            if (Schema::hasColumn('notifications', 'notifiable_type')) {
                $table->dropColumn('notifiable_type');
            }
            
            if (Schema::hasColumn('notifications', 'notifiable_id')) {
                $table->dropColumn('notifiable_id');
            }
            
            if (Schema::hasColumn('notifications', 'data')) {
                $table->dropColumn('data');
            }
            
            if (Schema::hasColumn('notifications', 'read_at')) {
                $table->dropColumn('read_at');
            }
            
            // Drop indexes if they exist
            if (Schema::hasIndex('notifications', 'notifications_notifiable_type_notifiable_id_index')) {
                $table->dropIndex(['notifiable_type', 'notifiable_id']);
            }
        });
    }
};