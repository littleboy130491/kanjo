<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Models\Proposal;
use App\Services\AiChatbot\ProposalChatbot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ProposalChatController extends Controller
{
    private const SESSION_CONVERSATIONS_LIMIT = 20;

    public function send(Request $request, ProposalChatbot $chatbot): JsonResponse
    {
        /** @var Proposal|null $proposal */
        $proposal = $request->attributes->get('document');

        abort_unless($proposal && $chatbot->isEnabledFor($proposal), 404);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:'.ProposalChatbot::MAX_MESSAGE_LENGTH],
            'conversation' => ['nullable', 'string', 'uuid'],
            'locale' => ['nullable', 'string'],
        ]);

        $message = trim($data['message']);

        if ($message === '') {
            return response()->json(['message' => 'The message field is required.'], 422);
        }

        $rateKey = self::rateLimitKey($proposal, $request);
        $maxPerMinute = max(1, $chatbot->settings()->rate_limit_per_minute);

        if (RateLimiter::tooManyAttempts($rateKey, $maxPerMinute)) {
            return response()->json([
                'message' => 'Too many messages. Please wait a moment.',
                'retry_after' => RateLimiter::availableIn($rateKey),
            ], 429);
        }

        RateLimiter::hit($rateKey, 60);

        $locale = $this->resolveLocale($proposal, $data['locale'] ?? null);
        $conversation = $this->resolveConversation($request, $proposal, $data['conversation'] ?? null)
            ?? $this->startConversation($request, $chatbot, $proposal, $locale);

        if ($conversation->userMessagesCount() >= ProposalChatbot::MAX_USER_MESSAGES) {
            return response()->json([
                'message' => 'This chat has reached its message limit. Please start a new chat.',
                'code' => 'conversation_limit',
            ], 422);
        }

        $reply = $chatbot->reply($conversation, $message, $locale);

        return response()->json([
            'conversation' => $conversation->uuid,
            'reply' => [
                'html' => ProposalChatbot::toHtml($reply->content),
                'failed' => filled($reply->error),
            ],
        ]);
    }

    public static function sessionKey(Proposal $proposal): string
    {
        return "ai_chat_conversations.{$proposal->id}";
    }

    public static function rateLimitKey(Proposal $proposal, Request $request): string
    {
        return 'ai-chat:'.$proposal->id.':'.$request->ip();
    }

    /**
     * Only continue a conversation that this browser session started on this proposal.
     */
    private function resolveConversation(Request $request, Proposal $proposal, ?string $uuid): ?AiConversation
    {
        if (blank($uuid) || ! in_array($uuid, (array) $request->session()->get(self::sessionKey($proposal), []), true)) {
            return null;
        }

        return $proposal->aiConversations()->where('uuid', $uuid)->first();
    }

    private function startConversation(Request $request, ProposalChatbot $chatbot, Proposal $proposal, string $locale): AiConversation
    {
        $conversation = $chatbot->startConversation($proposal, $locale, $request->ip(), $request->userAgent());

        $owned = collect((array) $request->session()->get(self::sessionKey($proposal), []))
            ->push($conversation->uuid)
            ->take(-self::SESSION_CONVERSATIONS_LIMIT)
            ->values()
            ->all();

        $request->session()->put(self::sessionKey($proposal), $owned);

        return $conversation;
    }

    private function resolveLocale(Proposal $proposal, ?string $requested): string
    {
        $default = (string) config('app.locale', 'en');

        if (! $proposal->activate_translation) {
            return $default;
        }

        return in_array($requested, config('app.supported_locales', ['en', 'id']), true)
            ? $requested
            : $default;
    }
}
