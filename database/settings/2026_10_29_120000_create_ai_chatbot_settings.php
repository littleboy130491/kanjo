<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('ai_chatbot.enabled', true);
        $this->migrator->add('ai_chatbot.provider', 'openai');
        $this->migrator->add('ai_chatbot.model', null);
        $this->migrator->add('ai_chatbot.temperature', 0.3);
        $this->migrator->add('ai_chatbot.max_tokens', 800);
        $this->migrator->add('ai_chatbot.bot_name', 'Assistant');
        $this->migrator->add('ai_chatbot.avatar', null);
        $this->migrator->add('ai_chatbot.accent_color', null);
        $this->migrator->add('ai_chatbot.welcome_message', [
            'en' => 'Hi! Ask me anything about this proposal.',
            'id' => 'Halo! Silakan tanyakan apa saja tentang proposal ini.',
        ]);
        $this->migrator->add('ai_chatbot.default_context', null);
        $this->migrator->add('ai_chatbot.quick_questions', [
            ['en' => 'What is included in this proposal?', 'id' => 'Apa saja yang termasuk dalam proposal ini?'],
            ['en' => 'How much does it cost?', 'id' => 'Berapa biayanya?'],
            ['en' => 'How long will the project take?', 'id' => 'Berapa lama pengerjaan proyeknya?'],
            ['en' => 'What are the payment terms?', 'id' => 'Bagaimana ketentuan pembayarannya?'],
        ]);
        $this->migrator->add('ai_chatbot.rate_limit_per_minute', 10);
    }
};
