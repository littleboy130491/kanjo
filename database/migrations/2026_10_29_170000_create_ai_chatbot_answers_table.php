<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chatbot_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            // null = shared by every visitor of the proposal (first question of a chat only).
            $table->foreignId('ai_conversation_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('locale', 5)->default('en');
            $table->string('question_hash', 64);
            $table->string('prompt_hash', 64);
            $table->text('content');
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['proposal_id', 'locale']);
            $table->index('last_used_at');
            $table->unique(
                ['proposal_id', 'ai_conversation_id', 'locale', 'question_hash', 'prompt_hash'],
                'ai_chatbot_answers_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chatbot_answers');
    }
};
