<?php

namespace Tests\Feature;

use App\Ai\Agents\ProposalAssistant;
use App\Enums\DocumentStatus;
use App\Http\Middleware\DocumentAccessMiddleware;
use App\Models\AiConversationMessage;
use App\Models\Company;
use App\Models\Proposal;
use App\Services\AiChatbot\ProposalChatbot;
use App\Settings\AiChatbotSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Prompts\AgentPrompt;
use Tests\TestCase;

class ProposalAiChatbotCachingTest extends TestCase
{
    use RefreshDatabase;

    public function test_anthropic_caches_the_system_prompt_and_the_conversation(): void
    {
        $options = (new ProposalAssistant('SYSTEM PROMPT'))->providerOptions('anthropic');

        $this->assertSame(['type' => 'ephemeral'], $options['cache_control']);
        $this->assertSame([[
            'type' => 'text',
            'text' => 'SYSTEM PROMPT',
            'cache_control' => ['type' => 'ephemeral'],
        ]], $options['system']);
    }

    public function test_openrouter_enables_caching_only_for_anthropic_models_and_pins_the_session(): void
    {
        $claude = (new ProposalAssistant('S', sessionId: 'conv-1', model: 'anthropic/claude-haiku-5.5'))->providerOptions('openrouter');
        $default = (new ProposalAssistant('S', sessionId: 'conv-1'))->providerOptions('openrouter');
        $gpt = (new ProposalAssistant('S', sessionId: 'conv-1', model: 'openai/gpt-4o-mini'))->providerOptions('openrouter');

        $this->assertSame(['cache_control' => ['type' => 'ephemeral'], 'session_id' => 'conv-1'], $claude);
        $this->assertSame(['cache_control' => ['type' => 'ephemeral'], 'session_id' => 'conv-1'], $default);
        $this->assertSame(['session_id' => 'conv-1'], $gpt);
    }

    public function test_openai_uses_a_prompt_cache_key(): void
    {
        $options = (new ProposalAssistant('S', cacheKey: 'kanjo-proposal-1-en'))->providerOptions('openai');

        $this->assertSame(['prompt_cache_key' => 'kanjo-proposal-1-en'], $options);
    }

    public function test_cache_key_is_per_proposal_and_locale_and_session_is_per_conversation(): void
    {
        ProposalAssistant::fake(['One']);
        $proposal = $this->createProposal();

        $conversation = $this->withDocumentAccess($proposal)
            ->postJson(route('proposal.chat', ['slug' => $proposal->slug]), ['message' => 'Hi'])
            ->assertOk()
            ->json('conversation');

        ProposalAssistant::assertPrompted(function (AgentPrompt $prompt) use ($proposal, $conversation): bool {
            return $prompt->agent->providerOptions('openai') === ['prompt_cache_key' => "kanjo-proposal-{$proposal->id}-en"]
                && ($prompt->agent->providerOptions('openrouter')['session_id'] ?? null) === $conversation;
        });
    }

    public function test_system_prompt_is_identical_across_requests(): void
    {
        $proposal = $this->createProposal();
        $chatbot = app(ProposalChatbot::class);

        $first = $chatbot->systemPrompt($proposal, 'en');
        $this->travel(3)->hours();
        $second = $chatbot->systemPrompt($proposal->fresh(), 'en');

        $this->assertSame($first, $second);
    }

    public function test_anthropic_request_sends_cache_control_and_logs_cache_usage(): void
    {
        config(['ai.providers.anthropic.key' => 'test-key']);
        app(AiChatbotSettings::class)->fill(['provider' => 'anthropic', 'model' => 'claude-haiku-5-5'])->save();

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'id' => 'msg_1',
                'type' => 'message',
                'role' => 'assistant',
                'model' => 'claude-haiku-5-5',
                'content' => [['type' => 'text', 'text' => 'It costs IDR 15.000.000.']],
                'stop_reason' => 'end_turn',
                'usage' => [
                    'input_tokens' => 12,
                    'output_tokens' => 9,
                    'cache_creation_input_tokens' => 40,
                    'cache_read_input_tokens' => 5600,
                ],
            ]),
        ]);

        $proposal = $this->createProposal();

        $this->withDocumentAccess($proposal)
            ->postJson(route('proposal.chat', ['slug' => $proposal->slug]), ['message' => 'How much?'])
            ->assertOk()
            ->assertJsonPath('reply.failed', false);

        Http::assertSent(function (Request $request): bool {
            $body = $request->data();

            return ($body['cache_control'] ?? null) === ['type' => 'ephemeral']
                && is_array($body['system'] ?? null)
                && ($body['system'][0]['cache_control'] ?? null) === ['type' => 'ephemeral']
                && str_contains($body['system'][0]['text'] ?? '', 'Project brief')
                && ! array_key_exists('temperature', $body);
        });

        $reply = AiConversationMessage::query()->where('role', AiConversationMessage::ROLE_ASSISTANT)->sole();
        $this->assertNull($reply->error);
        $this->assertSame(12, $reply->input_tokens);
        $this->assertSame(5600, $reply->cache_read_tokens);
        $this->assertSame(40, $reply->cache_write_tokens);
        $this->assertSame(9, $reply->output_tokens);
    }

    private function withDocumentAccess(Proposal $proposal): static
    {
        $this->withoutVite();

        return $this->withSession([
            DocumentAccessMiddleware::sessionKey('proposal', $proposal->id) => true,
            DocumentAccessMiddleware::versionKey('proposal', $proposal->id) => DocumentAccessMiddleware::credentialVersion($proposal),
        ]);
    }

    private function createProposal(): Proposal
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test Admin',
            'email' => 'admin@example.test',
            'password' => 'not-used-by-this-test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $company = Company::query()->create([
            'company_name' => 'PT Example Agency',
            'brand_name' => 'Example Agency',
            'address' => 'Jakarta',
            'email_1' => 'hello@example.test',
            'phone_1' => '08123456789',
            'tax_id' => 'NPWP-001',
            'default_currency' => 'IDR',
            'color_primary' => '#164e63',
            'color_secondary' => '#0f766e',
            'footer_text' => ['en' => 'Example footer', 'id' => 'Contoh footer'],
            'bank' => [],
            'pic' => [],
        ]);

        return Proposal::query()->create([
            'client_company' => 'PT Example Client',
            'client_name' => 'Rina',
            'client_email' => 'rina@example.test',
            'client_phone' => '081298765432',
            'issue_date' => '2026-08-31',
            'valid_until' => '2026-09-30',
            'currency' => 'IDR',
            'brief' => ['en' => '<p>Project brief</p>', 'id' => '<p>Ringkasan proyek</p>'],
            'offer_name_1' => 'Website Design & Development',
            'offer_1_price' => 15000000,
            'status' => DocumentStatus::PUBLISHED,
            'user_id' => $userId,
            'company_id' => $company->id,
            'notes' => [],
        ])->refresh();
    }
}
