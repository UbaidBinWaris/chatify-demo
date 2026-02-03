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
        // Add indexes to ch_messages table
        if (!$this->indexExists('ch_messages', 'idx_messages_conversation')) {
            DB::statement('ALTER TABLE ch_messages ADD INDEX idx_messages_conversation(from_id, to_id, created_at)');
        }
        
        if (!$this->indexExists('ch_messages', 'idx_messages_created_at')) {
            DB::statement('ALTER TABLE ch_messages ADD INDEX idx_messages_created_at(created_at)');
        }
        
        if (!$this->indexExists('ch_messages', 'idx_messages_unread')) {
            DB::statement('ALTER TABLE ch_messages ADD INDEX idx_messages_unread(to_id, seen, created_at)');
        }

        // Add indexes to users table
        if (!$this->indexExists('users', 'idx_users_name')) {
            DB::statement('ALTER TABLE users ADD INDEX idx_users_name(name)');
        }
        
        if (!$this->indexExists('users', 'idx_users_active_status')) {
            DB::statement('ALTER TABLE users ADD INDEX idx_users_active_status(active_status)');
        }
        
        // Add FULLTEXT index for search
        if (DB::getDriverName() === 'mysql' && !$this->indexExists('users', 'idx_users_search')) {
            try {
                DB::statement('ALTER TABLE users ADD FULLTEXT idx_users_search (name, email)');
            } catch (\Exception $e) {
                // Index might already exist, skip
            }
        }

        // Add indexes to ch_favorites table
        if (!$this->indexExists('ch_favorites', 'idx_favorites_user_favorite')) {
            DB::statement('ALTER TABLE ch_favorites ADD INDEX idx_favorites_user_favorite(user_id, favorite_id)');
        }
    }

    /**
     * Check if an index exists on a table (MySQL specific)
     */
    private function indexExists(string $table, string $indexName): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        $database = DB::getDatabaseName();
        
        $result = DB::select(
            "SELECT COUNT(*) as count 
             FROM information_schema.statistics 
             WHERE table_schema = ? 
             AND table_name = ? 
             AND index_name = ?",
            [$database, $table, $indexName]
        );

        return $result[0]->count > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->indexExists('ch_messages', 'idx_messages_conversation')) {
            DB::statement('ALTER TABLE ch_messages DROP INDEX idx_messages_conversation');
        }
        if ($this->indexExists('ch_messages', 'idx_messages_created_at')) {
            DB::statement('ALTER TABLE ch_messages DROP INDEX idx_messages_created_at');
        }
        if ($this->indexExists('ch_messages', 'idx_messages_unread')) {
            DB::statement('ALTER TABLE ch_messages DROP INDEX idx_messages_unread');
        }

        if ($this->indexExists('users', 'idx_users_name')) {
            DB::statement('ALTER TABLE users DROP INDEX idx_users_name');
        }
        if ($this->indexExists('users', 'idx_users_active_status')) {
            DB::statement('ALTER TABLE users DROP INDEX idx_users_active_status');
        }
        
        if (DB::getDriverName() === 'mysql' && $this->indexExists('users', 'idx_users_search')) {
            try {
                DB::statement('ALTER TABLE users DROP INDEX idx_users_search');
            } catch (\Exception $e) {
                // Index might not exist, skip
            }
        }

        if ($this->indexExists('ch_favorites', 'idx_favorites_user_favorite')) {
            DB::statement('ALTER TABLE ch_favorites DROP INDEX idx_favorites_user_favorite');
        }
    }
};
