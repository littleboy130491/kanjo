<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A stored answer, reused instead of calling the AI again.
 *
 * ai_conversation_id is null for answers shared by every visitor of the proposal;
 * those are only used for the first question of a chat.
 */
class AiChatbotAnswer extends Model
{
    protected $fillable = [
        'proposal_id',
        'ai_conversation_id',
        'locale',
        'question_hash',
        'prompt_hash',
        'content',
        'provider',
        'model',
        'hits',
        'last_used_at',
    ];

    protected $casts = [
        'hits' => 'integer',
        'last_used_at' => 'datetime',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class)->withTrashed();
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }
}
