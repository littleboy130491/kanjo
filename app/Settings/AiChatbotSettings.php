<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class AiChatbotSettings extends Settings
{
    public bool $enabled = true;

    public string $provider = 'openai';

    public ?string $model = null;

    public float $temperature = 0.3;

    public int $max_tokens = 800;

    public string $bot_name = 'Assistant';

    public ?int $avatar = null;

    public ?string $accent_color = null;

    public array $welcome_message = [];

    public ?string $instructions = null;

    public ?string $default_context = null;

    public array $quick_questions = [];

    public int $rate_limit_per_minute = 10;

    public static function group(): string
    {
        return 'ai_chatbot';
    }
}
