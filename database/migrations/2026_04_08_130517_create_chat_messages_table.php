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
        Schema::create('chat_messages', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Foreign keys to offices table
            $table->foreignId('sender_office_id')
                  ->constrained('offices')
                  ->onDelete('cascade')
                  ->comment('Office who sent the message');
                  
            $table->foreignId('receiver_office_id')
                  ->constrained('offices')
                  ->onDelete('cascade')
                  ->comment('Office who should receive the message');
            
            // Message type
            $table->enum('type', ['text', 'image', 'file'])
                  ->default('text')
                  ->comment('Type of message: text, image, or file');
            
            // Message content
            $table->longText('content')
                  ->comment('Message content (text or file path)');
            
            // File metadata (only for image/file types)
            $table->string('file_name')->nullable()
                  ->comment('Original file name');
                  
            $table->string('file_path')->nullable()
                  ->comment('Storage path of the file');
                  
            $table->string('file_mime_type')->nullable()
                  ->comment('MIME type of the file (e.g., image/jpeg, application/pdf)');
                  
            $table->integer('file_size')->nullable()
                  ->comment('File size in bytes');
            
            // Read receipt
            $table->boolean('is_read')->default(false)
                  ->comment('Whether the message has been read by receiver');
                  
            $table->timestamp('read_at')->nullable()
                  ->comment('When the message was read');
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['sender_office_id', 'receiver_office_id'], 'idx_sender_receiver');
            $table->index(['receiver_office_id', 'is_read'], 'idx_receiver_unread');
            $table->index(['receiver_office_id', 'created_at'], 'idx_receiver_created');
            $table->index(['sender_office_id', 'created_at'], 'idx_sender_created');
            $table->index('type', 'idx_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};