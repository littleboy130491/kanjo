<?php

namespace App\Services\AiChatbot;

use App\Models\AiChatbotAnswer;
use App\Models\AiConversation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Stores answers so identical questions are not sent to the AI again.
 *
 * An answer is only reused when the question, the proposal, the locale, and the full
 * system prompt (instructions, default context, proposal content, extra context,
 * provider and model) are identical. Editing any of them changes the prompt hash and
 * the old answers simply stop matching.
 */
class AnswerCache
{
    public const RETENTION_DAYS = 30;

    /**
     * Questions that only differ by spacing, case, or closing punctuation share an answer.
     */
    public static function questionHash(string $question): string
    {
        $normalized = mb_strtolower(Str::squish($question));

        return hash('sha256', (string) preg_replace('/[\s?!.。！？]+$/u', '', $normalized));
    }

    public static function promptHash(string $systemPrompt, ?string $provider, ?string $model): string
    {
        return hash('sha256', $systemPrompt."\n---\n".($provider ?? '')."\n".($model ?? ''));
    }

    /**
     * Find a stored answer for this question.
     *
     * The first question of a chat may also use the answer shared by every visitor of
     * the proposal, because at that point the prompt is just the system prompt plus
     * this question. Later questions only reuse answers from the same conversation,
     * since earlier turns change what the AI would say.
     */
    public function find(AiConversation $conversation, string $questionHash, string $promptHash, bool $opening): ?AiChatbotAnswer
    {
        $scopes = $opening ? [null, $conversation->id] : [$conversation->id];

        foreach ($scopes as $conversationId) {
            $answer = AiChatbotAnswer::query()
                ->where('proposal_id', $conversation->proposal_id)
                ->where('locale', $conversation->locale)
                ->where('question_hash', $questionHash)
                ->where('prompt_hash', $promptHash)
                ->when(
                    $conversationId === null,
                    fn ($query) => $query->whereNull('ai_conversation_id'),
                    fn ($query) => $query->where('ai_conversation_id', $conversationId),
                )
                ->latest('id')
                ->first();

            if ($answer === null) {
                continue;
            }

            $answer->forceFill([
                'hits' => $answer->hits + 1,
                'last_used_at' => Carbon::now(),
            ])->save();

            return $answer;
        }

        return null;
    }

    /**
     * Store an answer produced by the AI. Opening questions are also shared, so other
     * visitors of the proposal can reuse them without calling the AI.
     */
    public function remember(
        AiConversation $conversation,
        string $questionHash,
        string $promptHash,
        string $content,
        ?string $provider,
        ?string $model,
        bool $opening,
    ): void {
        $this->put($conversation, $conversation->id, $questionHash, $promptHash, $content, $provider, $model);

        if ($opening) {
            $this->put($conversation, null, $questionHash, $promptHash, $content, $provider, $model);
        }
    }

    /**
     * Keep a shared answer in this conversation too, so asking it again later in the
     * same chat does not call the AI.
     */
    public function rememberForConversation(
        AiConversation $conversation,
        string $questionHash,
        string $promptHash,
        string $content,
        ?string $provider,
        ?string $model,
    ): void {
        $this->put($conversation, $conversation->id, $questionHash, $promptHash, $content, $provider, $model);
    }

    public function pruneOlderThanDays(int $days = self::RETENTION_DAYS): int
    {
        $cutoff = Carbon::now()->subDays($days);

        return AiChatbotAnswer::query()
            ->where(fn ($query) => $query
                ->where('last_used_at', '<=', $cutoff)
                ->orWhere(fn ($query) => $query
                    ->whereNull('last_used_at')
                    ->where('created_at', '<=', $cutoff)))
            ->delete();
    }

    private function put(
        AiConversation $conversation,
        ?int $conversationId,
        string $questionHash,
        string $promptHash,
        string $content,
        ?string $provider,
        ?string $model,
    ): void {
        AiChatbotAnswer::query()->updateOrCreate(
            [
                'proposal_id' => $conversation->proposal_id,
                'ai_conversation_id' => $conversationId,
                'locale' => $conversation->locale,
                'question_hash' => $questionHash,
                'prompt_hash' => $promptHash,
            ],
            [
                'content' => $content,
                'provider' => $provider,
                'model' => $model,
            ],
        );
    }
}
