<?php

namespace Tests\Feature;

use App\Ai\Agents\ProposalAssistant;
use App\Enums\DocumentStatus;
use App\Http\Controllers\ProposalChatController;
use App\Http\Middleware\DocumentAccessMiddleware;
use App\Models\AiConversation;
use App\Models\AiConversationMessage;
use App\Models\Company;
use App\Models\Proposal;
use App\Services\AiChatbot\ProposalChatbot;
use App\Settings\AiChatbotSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Prompts\AgentPrompt;
use RuntimeException;
use Tests\TestCase;

class ProposalAiChatbotTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->userId = DB::table('users')->insertGetId([
            'name' => 'Test Admin',
            'email' => 'admin@example.test',
            'password' => 'not-used-by-this-test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->company = Company::query()->create([
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
    }

    public function test_new_proposals_have_the_chatbot_enabled_by_default(): void
    {
        $this->assertTrue($this->createProposal()->ai_chatbot_enabled);
    }

    public function test_widget_renders_on_the_public_proposal(): void
    {
        $proposal = $this->createProposal([
            'ai_chatbot_quick_questions' => [['en' => 'Is hosting included?', 'id' => 'Apakah hosting termasuk?']],
        ]);

        $this->withDocumentAccess($proposal)
            ->get(route('proposal.show', ['slug' => $proposal->slug]))
            ->assertOk()
            ->assertSee('data-ai-chat', false)
            ->assertSee(str_replace('/', '\/', route('proposal.chat', ['slug' => $proposal->slug])), false)
            ->assertSee('Is hosting included?');
    }

    public function test_widget_is_hidden_when_disabled_on_the_proposal(): void
    {
        $proposal = $this->createProposal(['ai_chatbot_enabled' => false]);

        $this->withDocumentAccess($proposal)
            ->get(route('proposal.show', ['slug' => $proposal->slug]))
            ->assertOk()
            ->assertDontSee('data-ai-chat', false);
    }

    public function test_widget_is_hidden_when_disabled_globally(): void
    {
        $this->updateSettings(['enabled' => false]);
        $proposal = $this->createProposal();

        $this->withDocumentAccess($proposal)
            ->get(route('proposal.show', ['slug' => $proposal->slug]))
            ->assertOk()
            ->assertDontSee('data-ai-chat', false);
    }

    public function test_widget_is_never_rendered_in_pdf_mode(): void
    {
        $proposal = $this->createProposal();

        $html = view('proposals.show', [
            'proposal' => $proposal,
            'locale' => 'en',
            'slug' => $proposal->slug,
            'pdf' => true,
        ])->render();

        $this->assertStringNotContainsString('data-ai-chat', $html);
    }

    public function test_chat_requires_document_access(): void
    {
        config([
            'app.global_access_username' => 'viewer',
            'app.global_access_password' => 'secret',
        ]);
        ProposalAssistant::fake()->preventStrayPrompts();
        $proposal = $this->createProposal();

        $this->postJson(route('proposal.chat', ['slug' => $proposal->slug]), ['message' => 'Hi'])
            ->assertUnauthorized();

        ProposalAssistant::assertNeverPrompted();
        $this->assertSame(0, AiConversation::query()->count());
    }

    public function test_chat_is_not_available_when_disabled_on_the_proposal(): void
    {
        $proposal = $this->createProposal(['ai_chatbot_enabled' => false]);

        $this->withDocumentAccess($proposal)
            ->postJson(route('proposal.chat', ['slug' => $proposal->slug]), ['message' => 'Hi'])
            ->assertNotFound();
    }

    public function test_chat_logs_the_visitor_message_and_the_ai_reply(): void
    {
        ProposalAssistant::fake(['It costs **IDR 15.000.000**.']);
        $proposal = $this->createProposal();

        $response = $this->withDocumentAccess($proposal)
            ->postJson(route('proposal.chat', ['slug' => $proposal->slug]), ['message' => 'How much is it?'])
            ->assertOk()
            ->assertJsonPath('reply.failed', false);

        $this->assertStringContainsString('<strong>IDR 15.000.000</strong>', $response->json('reply.html'));

        $conversation = $proposal->aiConversations()->sole();
        $this->assertSame($response->json('conversation'), $conversation->uuid);
        $this->assertSame(2, $conversation->refresh()->messages_count);
        $this->assertSame(
            [
                [AiConversationMessage::ROLE_USER, 'How much is it?'],
                [AiConversationMessage::ROLE_ASSISTANT, 'It costs **IDR 15.000.000**.'],
            ],
            $conversation->messages->map(fn ($message) => [$message->role, $message->content])->all(),
        );
    }

    public function test_ai_reply_html_strips_raw_html(): void
    {
        ProposalAssistant::fake(['Hello <script>alert(1)</script> there']);
        $proposal = $this->createProposal();

        $html = $this->withDocumentAccess($proposal)
            ->postJson(route('proposal.chat', ['slug' => $proposal->slug]), ['message' => 'Hi'])
            ->assertOk()
            ->json('reply.html');

        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_follow_up_messages_reuse_the_conversation_and_server_history(): void
    {
        ProposalAssistant::fake(['First answer', 'Second answer']);
        $proposal = $this->createProposal();

        $first = $this->withDocumentAccess($proposal)
            ->postJson(route('proposal.chat', ['slug' => $proposal->slug]), ['message' => 'First question'])
            ->json('conversation');

        $this->postJson(route('proposal.chat', ['slug' => $proposal->slug]), [
            'message' => 'Second question',
            'conversation' => $first,
        ])->assertOk()->assertJsonPath('conversation', $first);

        $this->assertSame(1, AiConversation::query()->count());
        $this->assertSame(4, AiConversation::query()->sole()->messages_count);

        ProposalAssistant::assertPrompted(function (AgentPrompt $prompt): bool {
            $history = collect($prompt->agent->messages())->map(fn ($message) => $message->content)->all();

            return $prompt->contains('Second question') && $history === ['First question', 'First answer'];
        });
    }

    public function test_conversation_from_another_session_or_proposal_is_not_continued(): void
    {
        ProposalAssistant::fake(['Answer']);
        $proposal = $this->createProposal();
        $other = $this->createProposal(['client_company' => 'PT Other Client']);
        $foreign = $other->aiConversations()->create(['locale' => 'en']);

        $response = $this->withDocumentAccess($proposal)
            ->withSession([ProposalChatController::sessionKey($proposal) => [$foreign->uuid]])
            ->postJson(route('proposal.chat', ['slug' => $proposal->slug]), [
                'message' => 'Hi',
                'conversation' => $foreign->uuid,
            ])
            ->assertOk();

        $this->assertNotSame($foreign->uuid, $response->json('conversation'));
        $this->assertSame(0, $foreign->messages()->count());
        $this->assertSame(1, $proposal->aiConversations()->count());
    }

    public function test_failed_ai_calls_are_logged_with_the_error(): void
    {
        ProposalAssistant::fake(fn () => throw new RuntimeException('Provider is down'));
        $proposal = $this->createProposal();

        $this->withDocumentAccess($proposal)
            ->postJson(route('proposal.chat', ['slug' => $proposal->slug]), ['message' => 'Hi'])
            ->assertOk()
            ->assertJsonPath('reply.failed', true);

        $reply = AiConversationMessage::query()->where('role', AiConversationMessage::ROLE_ASSISTANT)->sole();
        $this->assertStringContainsString('Provider is down', $reply->error);
        $this->assertNotEmpty($reply->content);
        $this->assertSame(1, AiConversationMessage::query()->where('role', AiConversationMessage::ROLE_USER)->count());
    }

    public function test_chat_is_rate_limited_per_proposal_and_ip(): void
    {
        $this->updateSettings(['rate_limit_per_minute' => 1]);
        ProposalAssistant::fake(['One', 'Two']);
        $proposal = $this->createProposal();
        $url = route('proposal.chat', ['slug' => $proposal->slug]);

        $this->withDocumentAccess($proposal)->postJson($url, ['message' => 'One'])->assertOk();
        $this->postJson($url, ['message' => 'Two'])->assertStatus(429);
    }

    public function test_system_prompt_only_contains_this_proposal_and_its_context(): void
    {
        $this->updateSettings(['default_context' => 'We reply within one business day.']);
        $proposal = $this->createProposal([
            'ai_chatbot_context' => 'Client prefers WhatsApp.',
            'notes' => [['note' => 'Internal: client is price sensitive']],
            'access_username' => 'secret-user',
        ]);
        $other = $this->createProposal([
            'client_company' => 'PT Competitor',
            'ai_chatbot_context' => 'Other proposal context',
        ]);

        $prompt = app(ProposalChatbot::class)->systemPrompt($proposal->refresh(), 'en');

        $this->assertStringContainsString('We reply within one business day.', $prompt);
        $this->assertStringContainsString('Client prefers WhatsApp.', $prompt);
        $this->assertStringContainsString('Project brief', $prompt);
        $this->assertStringContainsString('IDR 15.000.000', $prompt);
        $this->assertStringNotContainsString('price sensitive', $prompt);
        $this->assertStringNotContainsString('secret-user', $prompt);
        $this->assertStringNotContainsString('rina@example.test', $prompt);
        $this->assertStringNotContainsString($other->client_company, $prompt);
        $this->assertStringNotContainsString('Other proposal context', $prompt);
    }

    public function test_system_prompt_uses_the_visitor_locale(): void
    {
        $proposal = $this->createProposal();

        $prompt = app(ProposalChatbot::class)->systemPrompt($proposal, 'id');

        $this->assertStringContainsString('Ringkasan proyek', $prompt);
        $this->assertStringContainsString('Indonesian', $prompt);
    }

    public function test_proposal_quick_questions_override_the_global_list(): void
    {
        $this->updateSettings(['quick_questions' => [['en' => 'Global question', 'id' => 'Pertanyaan global']]]);
        $chatbot = app(ProposalChatbot::class);

        $this->assertSame(['Pertanyaan global'], $chatbot->quickQuestions($this->createProposal(), 'id'));
        $this->assertSame(
            ['Own question'],
            $chatbot->quickQuestions($this->createProposal([
                'ai_chatbot_quick_questions' => [['en' => 'Own question', 'id' => null]],
            ]), 'id'),
        );
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function updateSettings(array $values): void
    {
        $settings = app(AiChatbotSettings::class);
        $settings->fill($values)->save();
    }

    private function withDocumentAccess(Proposal $proposal): static
    {
        return $this->withSession([
            DocumentAccessMiddleware::sessionKey('proposal', $proposal->id) => true,
            DocumentAccessMiddleware::versionKey('proposal', $proposal->id) => DocumentAccessMiddleware::credentialVersion($proposal),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createProposal(array $attributes = []): Proposal
    {
        return Proposal::query()->create(array_merge([
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
            'user_id' => $this->userId,
            'company_id' => $this->company->id,
            'notes' => [],
        ], $attributes))->refresh();
    }
}
