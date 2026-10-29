<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5)->default('en');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedInteger('messages_count')->default(0);
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('ai_conversation_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ai_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->text('content');
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['ai_conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversation_messages');
        Schema::dropIfExists('ai_conversations');
    }
};
