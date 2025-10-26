<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, let's check the current structure of the notifications table
        // We need to fix the ID field to be auto-incrementing

        // Since we can't directly modify the id column type in some cases,
        // we'll recreate the table with the correct structure

        // But first, let's check if the id column is already auto-incrementing
        $columns = DB::select("SHOW COLUMNS FROM notifications WHERE Field = 'id'");

        if (!empty($columns)) {
            $idColumn = $columns[0];
            // Check if it's already auto-incrementing
            if (strpos($idColumn->Extra, 'auto_increment') === false) {
                Schema::create('notifications_new', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id')->nullable();
                    $table->string('type', 191);
                    $table->string('title', 191)->nullable();
                    $table->text('message')->nullable();
                    $table->string('related_url', 191)->nullable();
                    $table->boolean('is_read')->default(false);
                    $table->timestamp('read_at')->nullable();
                    $table->text('data')->nullable();
                    $table->string('notifiable_type', 191);
                    $table->unsignedBigInteger('notifiable_id');
                    $table->timestamps();

                    $table->index(['user_id']);
                    $table->index(['notifiable_type', 'notifiable_id']);

                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                });

                DB::statement('INSERT INTO notifications_new 
                    (user_id, type, title, message, related_url, is_read, read_at, data, notifiable_type, notifiable_id, created_at, updated_at)
                    SELECT user_id, type, title, message, related_url, is_read, read_at, data, notifiable_type, notifiable_id, created_at, updated_at 
                    FROM notifications');

                Schema::dropIfExists('notifications');

                Schema::rename('notifications_new', 'notifications');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not easily reversible, so we'll leave it as is
        // In a production environment, you would want to be more careful
    }
};
