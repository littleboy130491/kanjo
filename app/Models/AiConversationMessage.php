<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConversationMessage extends Model
{
    public const ROLE_USER = 'user';

    public const ROLE_ASSISTANT = 'assistant';

    protected $fillable = [
        'ai_conversation_id',
        'role',
        'content',
        'provider',
        'model',
        'input_tokens',
        'cache_read_tokens',
        'cache_write_tokens',
        'output_tokens',
        'error',
        'cache_hit',
    ];

    protected $casts = [
        'input_tokens' => 'integer',
        'cache_read_tokens' => 'integer',
        'cache_write_tokens' => 'integer',
        'output_tokens' => 'integer',
        'cache_hit' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::created(function (self $message): void {
            AiConversation::query()
                ->whereKey($message->ai_conversation_id)
                ->update([
                    'messages_count' => AiConversationMessage::query()
                        ->where('ai_conversation_id', $message->ai_conversation_id)
                        ->count(),
                    'last_message_at' => $message->created_at,
                ]);
        });
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }
}
