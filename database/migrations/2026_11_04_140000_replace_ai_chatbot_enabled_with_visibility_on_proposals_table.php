<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table): void {
            $table->string('ai_chatbot_visibility', 10)
                ->default('default')
                ->after('show_team_member');
        });

        // A proposal that turned the chatbot off keeps it off; every other proposal follows the global switch.
        DB::table('proposals')->where('ai_chatbot_enabled', false)->update(['ai_chatbot_visibility' => 'off']);

        Schema::table('proposals', function (Blueprint $table): void {
            $table->dropColumn('ai_chatbot_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table): void {
            $table->boolean('ai_chatbot_enabled')->default(true)->after('show_team_member');
        });

        DB::table('proposals')->where('ai_chatbot_visibility', 'off')->update(['ai_chatbot_enabled' => false]);

        Schema::table('proposals', function (Blueprint $table): void {
            $table->dropColumn('ai_chatbot_visibility');
        });
    }
};
