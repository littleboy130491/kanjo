<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AiConversation extends Model
{
    protected $fillable = [
        'uuid',
        'proposal_id',
        'locale',
        'ip_address',
        'user_agent',
        'messages_count',
        'last_message_at',
    ];

    protected $casts = [
        'messages_count' => 'integer',
        'last_message_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $conversation): void {
            if (blank($conversation->uuid)) {
                $conversation->uuid = (string) Str::uuid();
            }
        });
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class)->withTrashed();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiConversationMessage::class)->orderBy('id');
    }

    public function scopeWithSummary(Builder $query): Builder
    {
        return $query
            ->select('ai_conversations.*')
            ->addSelect([
                'first_question' => AiConversationMessage::query()
                    ->select('content')
                    ->whereColumn('ai_conversation_id', 'ai_conversations.id')
                    ->where('role', AiConversationMessage::ROLE_USER)
                    ->orderBy('id')
                    ->limit(1),
            ])
            ->withCount([
                'messages as failed_messages_count' => fn (Builder $messages) => $messages->whereNotNull('error'),
            ]);
    }

    public function userMessagesCount(): int
    {
        return $this->messages()->where('role', AiConversationMessage::ROLE_USER)->count();
    }
}
