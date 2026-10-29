<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->boolean('ai_chatbot_enabled')->default(true)->after('show_team_member');
            $table->text('ai_chatbot_context')->nullable()->after('ai_chatbot_enabled');
            $table->json('ai_chatbot_quick_questions')->nullable()->after('ai_chatbot_context');
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn([
                'ai_chatbot_enabled',
                'ai_chatbot_context',
                'ai_chatbot_quick_questions',
            ]);
        });
    }
};
