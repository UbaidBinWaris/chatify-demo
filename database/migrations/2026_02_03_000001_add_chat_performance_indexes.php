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
            // Check and add indexes only if they don't exist
            if (!$this->indexExists('ch_messages', 'idx_messages_conversation')) {
                $table->index(['from_id', 'to_id', 'created_at'], 'idx_messages_conversation');
            }
            
            if (!$this->indexExists('ch_messages', 'idx_messages_created_at')) {
                $table->index('created_at', 'idx_messages_created_at');
            }
            
            if (!$this->indexExists('ch_messages', 'idx_messages_unread')) {
                $table->index(['to_id', 'seen', 'created_at'], 'idx_messages_unread');
            }
            
            // Skip attachment index - BLOB/TEXT columns need special handling
            // and attachments are typically queried by message, not directly
        });

        Schema::table('users', function (Blueprint $table) {
            if (!$this->indexExists('users', 'idx_users_name')) {
                $table->index('name', 'idx_users_name');
            }
            
            if (!$this->indexExists('users', 'idx_users_active_status')) {
                $table->index('active_status', 'idx_users_active_status');
            }
            
            // Add FULLTEXT index for search if it doesn't exist
            if (DB::getDriverName() === 'mysql' && !$this->indexExists('users', 'idx_users_search')) {
                try {
                    DB::statement('ALTER TABLE users ADD FULLTEXT idx_users_search (name, email)');
                } catch (\Exception $e) {
                    // Index might already exist, skip
                }
            }
        });

        Schema::table('ch_favorites', function (Blueprint $table) {
            if (!$this->indexExists('ch_favorites', 'idx_favorites_user_favorite')) {
                $table->index(['user_id', 'favorite_id'], 'idx_favorites_user_favorite');
            }
        });
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $schemaManager = $connection->getDoctrineSchemaManager();
        
        try {
            $indexes = $schemaManager->listTableIndexes($table);
            return isset($indexes[$indexName]);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ch_messages', function (Blueprint $table) {
            if ($this->indexExists('ch_messages', 'idx_messages_conversation')) {
                $table->dropIndex('idx_messages_conversation');
            }
            if ($this->indexExists('ch_messages', 'idx_messages_created_at')) {
                $table->dropIndex('idx_messages_created_at');
            }
            if ($this->indexExists('ch_messages', 'idx_messages_unread')) {
                $table->dropIndex('idx_messages_unread');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if ($this->indexExists('users', 'idx_users_name')) {
                $table->dropIndex('idx_users_name');
            }
            if ($this->indexExists('users', 'idx_users_active_status')) {
                $table->dropIndex('idx_users_active_status');
            }
            
            if (DB::getDriverName() === 'mysql' && $this->indexExists('users', 'idx_users_search')) {
                try {
                    DB::statement('ALTER TABLE users DROP INDEX idx_users_search');
                } catch (\Exception $e) {
                    // Index might not exist, skip
                }
            }
        });

        Schema::table('ch_favorites', function (Blueprint $table) {
            if ($this->indexExists('ch_favorites', 'idx_favorites_user_favorite')) {
                $table->dropIndex('idx_favorites_user_favorite');
            }
        });
    }
};
