<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Null means "use the built-in default instructions".
        $this->migrator->add('ai_chatbot.instructions', null);
    }

    public function down(): void
    {
        $this->migrator->delete('ai_chatbot.instructions');
    }
};
