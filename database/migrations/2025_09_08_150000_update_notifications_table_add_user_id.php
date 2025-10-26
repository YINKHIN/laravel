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
        Schema::table('notifications', function (Blueprint $table) {
            // Add user_id column
            $table->unsignedBigInteger('user_id')->after('id')->nullable();
            
            // Add title, message, related_url columns
            $table->string('title')->after('type')->nullable();
            $table->text('message')->after('title')->nullable();
            $table->string('related_url')->after('message')->nullable();
            
            // Add is_read column
            $table->boolean('is_read')->default(false)->after('related_url');
            
            // Add index for user_id
            $table->index('user_id');
            
            // Add foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn(['user_id', 'title', 'message', 'related_url', 'is_read']);
        });
    }
};