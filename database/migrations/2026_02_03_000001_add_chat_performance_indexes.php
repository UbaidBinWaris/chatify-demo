<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds performance indexes for chat queries
     * to optimize contact list, message fetching, and search operations
     */
    public function up(): void
    {
        Schema::table('ch_messages', function (Blueprint $table) {
            // Composite index for fetching conversations (from_id, to_id, created_at)
            $table->index(['from_id', 'to_id', 'created_at'], 'idx_messages_conversation');
            
            // Index for sorting by created_at (latest messages first)
            $table->index('created_at', 'idx_messages_created_at');
            
            // Index for unread messages (seen status)
            $table->index(['to_id', 'seen', 'created_at'], 'idx_messages_unread');
            
            // Index for attachment queries
            $table->index('attachment', 'idx_messages_attachment');
        });

        Schema::table('users', function (Blueprint $table) {
            // Index for user search by name
            $table->index('name', 'idx_users_name');
            
            // Index for active status queries
            $table->index('active_status', 'idx_users_active_status');
            
            // Composite index for search (name, email)
            // Note: For PostgreSQL or MySQL 5.7+, consider FULLTEXT index
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE users ADD FULLTEXT idx_users_search (name, email)');
            }
        });

        Schema::table('ch_favorites', function (Blueprint $table) {
            // Composite index for favorite queries
            $table->index(['user_id', 'favorite_id'], 'idx_favorites_user_favorite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ch_messages', function (Blueprint $table) {
            $table->dropIndex('idx_messages_conversation');
            $table->dropIndex('idx_messages_created_at');
            $table->dropIndex('idx_messages_unread');
            $table->dropIndex('idx_messages_attachment');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_name');
            $table->dropIndex('idx_users_active_status');
            
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE users DROP INDEX idx_users_search');
            }
        });

        Schema::table('ch_favorites', function (Blueprint $table) {
            $table->dropIndex('idx_favorites_user_favorite');
        });
    }
};
