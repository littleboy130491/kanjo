<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Answers visitor questions about a single proposal.
 * It has no tools, so it can only use the instructions and history it is given.
 */
class ProposalAssistant implements Agent, Conversational, HasProviderOptions
{
    use Promptable;

    /**
     * @param  array<int, Message>  $history
     * @param  string|null  $cacheKey  Same for every request on one proposal + locale (shared prompt cache).
     * @param  string|null  $sessionId  Same for every request in one conversation (sticky routing).
     */
    public function __construct(
        private string $systemPrompt,
        private array $history = [],
        private ?float $temperature = null,
        private ?int $maxTokens = null,
        private ?string $cacheKey = null,
        private ?string $sessionId = null,
        private ?string $model = null,
    ) {}

    public function instructions(): Stringable|string
    {
        return $this->systemPrompt;
    }

    public function messages(): iterable
    {
        return $this->history;
    }

    public function temperature(): ?float
    {
        return $this->temperature;
    }

    public function maxTokens(): ?int
    {
        return $this->maxTokens;
    }

    /**
     * Prompt caching per provider. The system prompt never changes within a proposal + locale,
     * so it is the shared cacheable prefix; the history and visitor message come after it.
     */
    public function providerOptions(Lab|string $provider): array
    {
        $provider = $provider instanceof Lab ? $provider->value : $provider;

        return match ($provider) {
            // Breakpoint on the system prompt (shared across visitors of this proposal)
            // plus automatic caching, which moves forward with the conversation history.
            Lab::Anthropic->value => [
                'cache_control' => ['type' => 'ephemeral'],
                'system' => [[
                    'type' => 'text',
                    'text' => $this->systemPrompt,
                    'cache_control' => ['type' => 'ephemeral'],
                ]],
            ],
            Lab::OpenRouter->value => array_filter([
                'cache_control' => $this->isAnthropicModel() ? ['type' => 'ephemeral'] : null,
                'session_id' => $this->sessionId,
            ]),
            Lab::OpenAI->value => array_filter([
                'prompt_cache_key' => $this->cacheKey,
            ]),
            default => [],
        };
    }

    private function isAnthropicModel(): bool
    {
        // An empty model uses the SDK's OpenRouter default, which is an Anthropic model.
        return blank($this->model)
            || preg_match('#^~?anthropic/#i', (string) $this->model) === 1;
    }
}
