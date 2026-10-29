<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Answers visitor questions about a single proposal.
 * It has no tools, so it can only use the instructions and history it is given.
 */
class ProposalAssistant implements Agent, Conversational
{
    use Promptable;

    /**
     * @param  array<int, Message>  $history
     */
    public function __construct(
        private string $systemPrompt,
        private array $history = [],
        private ?float $temperature = null,
        private ?int $maxTokens = null,
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
}
