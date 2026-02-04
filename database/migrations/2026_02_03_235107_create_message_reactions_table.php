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
        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('message_id');
            $table->bigInteger('user_id');
            $table->string('emoji', 10); // Stores the emoji (e.g., "👍", "❤️", "😂")
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('message_id')->references('id')->on('ch_messages')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Ensure one reaction per user per message
            $table->unique(['message_id', 'user_id']);
            
            // Index for faster queries
            $table->index(['message_id', 'emoji']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_reactions');
    }
};
