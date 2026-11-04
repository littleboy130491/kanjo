<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table): void {
            $table->string('ai_chatbot_quick_questions_visibility', 10)
                ->default('default')
                ->after('ai_chatbot_quick_questions');
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table): void {
            $table->dropColumn('ai_chatbot_quick_questions_visibility');
        });
    }
};
