<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Newer models (e.g. recent Claude models) reject `temperature`. Clear the old default so the model default is used.
        $this->migrator->update(
            'ai_chatbot.temperature',
            fn (mixed $temperature): ?float => $temperature === null || abs((float) $temperature - 0.3) < 0.0001
                ? null
                : (float) $temperature,
        );
    }
};
