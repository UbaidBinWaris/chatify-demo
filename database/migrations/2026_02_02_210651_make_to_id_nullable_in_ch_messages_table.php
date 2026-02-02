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
        // SQLite doesn't support modifying columns directly, so we need to recreate the table
        // First, create a temporary table with the correct structure
        Schema::create('ch_messages_temp', function (Blueprint $table) {
            $table->uuid('id')->primary();  // Use UUID like original table
            $table->unsignedBigInteger('from_id');
            $table->unsignedBigInteger('to_id')->nullable(); // Made nullable for group messages
            $table->unsignedBigInteger('group_id')->nullable();
            $table->string('body', 5000)->nullable();
            $table->text('attachment')->nullable();
            $table->boolean('seen')->default(0);
            $table->timestamps();
            
            $table->foreign('from_id')->references('id')->on('users')->onDelete('cascade');
            // Don't add foreign key on to_id since it can be null for group messages
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
        });
        
        // Copy data from old table to new table
        DB::statement('INSERT INTO ch_messages_temp (id, from_id, to_id, group_id, body, attachment, seen, created_at, updated_at) 
                      SELECT id, from_id, to_id, group_id, body, attachment, seen, created_at, updated_at 
                      FROM ch_messages');
        
        // Drop the old table
        Schema::dropIfExists('ch_messages');
        
        // Rename the temp table to the original name
        Schema::rename('ch_messages_temp', 'ch_messages');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the original structure
        Schema::create('ch_messages_temp', function (Blueprint $table) {
            $table->uuid('id')->primary();  // Use UUID like original table
            $table->unsignedBigInteger('from_id');
            $table->unsignedBigInteger('to_id'); // Back to NOT NULL
            $table->unsignedBigInteger('group_id')->nullable();
            $table->string('body', 5000)->nullable();
            $table->text('attachment')->nullable();
            $table->boolean('seen')->default(0);
            $table->timestamps();
            
            $table->foreign('from_id')->references('id')->on('users')->onDelete('cascade');
            // Don't add foreign key on to_id to match up() method
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
        });
        
        // Copy only messages where to_id is not null
        DB::statement('INSERT INTO ch_messages_temp (id, from_id, to_id, group_id, body, attachment, seen, created_at, updated_at) 
                      SELECT id, from_id, to_id, group_id, body, attachment, seen, created_at, updated_at 
                      FROM ch_messages WHERE to_id IS NOT NULL');
        
        Schema::dropIfExists('ch_messages');
        Schema::rename('ch_messages_temp', 'ch_messages');
    }
};
