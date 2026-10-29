<?php

namespace Tests\Feature;

use App\Ai\Agents\ProposalAssistant;
use App\Enums\DocumentStatus;
use App\Http\Middleware\DocumentAccessMiddleware;
use App\Models\AiChatbotAnswer;
use App\Models\AiConversationMessage;
use App\Models\Company;
use App\Models\Proposal;
use App\Services\AiChatbot\AnswerCache;
use App\Services\AiConversationPruner;
use App\Settings\AiChatbotSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

class ProposalAiChatbotAnswerCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_shared_question_is_answered_without_calling_the_ai_twice(): void
    {
        $calls = 0;
        ProposalAssistant::fake(function () use (&$calls) {
            $calls++;

            return 'It costs **IDR 15.000.000**.';
        });
        $proposal = $this->createProposal();

        $this->ask($proposal, 'How much does it cost?')->assertJsonPath('reply.failed', false);

        // A second visitor starts a new conversation and asks the same question.
        $this->ask($proposal, 'How much does it cost?')->assertJsonPath('reply.failed', false);

        $this->assertSame(1, $calls);

        $replies = AiConversationMessage::query()
            ->where('role', AiConversationMessage::ROLE_ASSISTANT)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $replies);
        $this->assertFalse($replies[0]->cache_hit);
        $this->assertTrue($replies[1]->cache_hit);
        $this->assertSame($replies[0]->content, $replies[1]->content);
        $this->assertNull($replies[1]->input_tokens);
        $this->assertNull($replies[1]->output_tokens);
        $this->assertSame(2, $proposal->aiConversations()->count());
    }

    public function test_a_different_question_still_calls_the_ai(): void
    {
        $calls = 0;
        ProposalAssistant::fake(function () use (&$calls) {
            $calls++;

            return 'Answer '.$calls;
        });
        $proposal = $this->createProposal();

        $this->ask($proposal, 'How much does it cost?');
        $this->ask($proposal, 'What is included?');

        $this->assertSame(2, $calls);
    }

    public function test_spacing_case_and_trailing_punctuation_share_an_answer(): void
    {
        $calls = 0;
        ProposalAssistant::fake(function () use (&$calls) {
            $calls++;

            return 'One answer';
        });
        $proposal = $this->createProposal();

        $this->ask($proposal, 'How much does it cost?');
        $this->ask($proposal, '  how much   does it COST ');

        $this->assertSame(1, $calls);
    }

    public function test_answers_are_not_shared_between_proposals(): void
    {
        $calls = 0;
        ProposalAssistant::fake(function () use (&$calls) {
            $calls++;

            return 'Answer '.$calls;
        });
        $proposal = $this->createProposal();
        $other = $this->createProposal(['client_company' => 'PT Other Client']);

        $this->ask($proposal, 'How much does it cost?');
        $this->ask($other, 'How much does it cost?');

        $this->assertSame(2, $calls);
        $this->assertSame(
            1,
            AiChatbotAnswer::query()->where('proposal_id', $proposal->id)->whereNull('ai_conversation_id')->count(),
        );
    }

    public function test_editing_the_proposal_invalidates_the_cached_answer(): void
    {
        $calls = 0;
        ProposalAssistant::fake(function () use (&$calls) {
            $calls++;

            return 'Answer '.$calls;
        });
        $proposal = $this->createProposal();

        $this->ask($proposal, 'How much does it cost?');

        $proposal->forceFill(['offer_1_price' => 20000000, 'brief' => ['en' => '<p>Updated brief</p>', 'id' => '<p>Ringkasan baru</p>']])->save();

        $this->ask($proposal->refresh(), 'How much does it cost?');

        $this->assertSame(2, $calls);
    }

    public function test_changing_the_chatbot_settings_invalidates_the_cached_answer(): void
    {
        $calls = 0;
        ProposalAssistant::fake(function () use (&$calls) {
            $calls++;

            return 'Answer '.$calls;
        });
        $proposal = $this->createProposal();

        $this->ask($proposal, 'How much does it cost?');

        app(AiChatbotSettings::class)
            ->fill(['default_context' => 'We reply within one business day.'])
            ->save();

        $this->ask($proposal, 'How much does it cost?');

        $this->assertSame(2, $calls);
    }

    public function test_the_same_question_later_in_a_chat_reuses_the_earlier_answer(): void
    {
        $calls = 0;
        ProposalAssistant::fake(function () use (&$calls) {
            $calls++;

            return 'Answer '.$calls;
        });
        $proposal = $this->createProposal();

        $conversation = $this->ask($proposal, 'How much does it cost?')->json('conversation');
        $this->ask($proposal, 'What is included?', $conversation);

        // Asked again after another turn: the answer from this chat is reused, the AI is not called.
        $repeat = $this->ask($proposal, 'How much does it cost?', $conversation)
            ->assertJsonPath('reply.failed', false);

        $this->assertSame(2, $calls);
        $this->assertStringContainsString('Answer 1', $repeat->json('reply.html'));
        $this->assertSame(1, AiConversationMessage::query()
            ->where('role', AiConversationMessage::ROLE_ASSISTANT)
            ->where('cache_hit', true)
            ->count());
    }

    public function test_a_shared_answer_is_not_used_for_a_later_question_in_another_chat(): void
    {
        $calls = 0;
        ProposalAssistant::fake(function () use (&$calls) {
            $calls++;

            return 'Answer '.$calls;
        });
        $proposal = $this->createProposal();

        // First visitor answers the question, which stores a shared answer.
        $this->ask($proposal, 'How much does it cost?');

        // Second visitor opens with a different question, then asks the shared one.
        $conversation = $this->ask($proposal, 'What is included?')->json('conversation');
        $this->ask($proposal, 'How much does it cost?', $conversation);

        $this->assertSame(3, $calls);
    }

    public function test_failed_replies_are_not_cached(): void
    {
        $calls = 0;
        ProposalAssistant::fake(function () use (&$calls) {
            $calls++;

            throw new RuntimeException('Provider is down');
        });
        $proposal = $this->createProposal();

        $this->ask($proposal, 'How much does it cost?')->assertJsonPath('reply.failed', true);
        $this->ask($proposal, 'How much does it cost?')->assertJsonPath('reply.failed', true);

        $this->assertSame(2, $calls);
        $this->assertSame(0, AiChatbotAnswer::query()->count());
    }

    public function test_cache_rows_unused_for_thirty_days_are_pruned(): void
    {
        $proposal = $this->createProposal();

        $stale = $this->makeCachedAnswer($proposal, null, now()->subDays(40));
        $usedRecently = $this->makeCachedAnswer($proposal, null, now()->subDays(40), now()->subDay());
        $fresh = $this->makeCachedAnswer($proposal, null, now()->subDay());

        $this->assertSame(1, app(AnswerCache::class)->pruneOlderThanDays(30));
        $this->assertModelMissing($stale);
        $this->assertModelExists($usedRecently);
        $this->assertModelExists($fresh);
    }

    public function test_pruning_conversations_also_prunes_stale_shared_answers(): void
    {
        $proposal = $this->createProposal();
        $stale = $this->makeCachedAnswer($proposal, null, now()->subDays(40));

        app(AiConversationPruner::class)->pruneOlderThanDays(90);

        $this->assertModelMissing($stale);
    }

    public function test_pruning_a_conversation_removes_its_cached_answers(): void
    {
        $proposal = $this->createProposal();
        $conversation = $proposal->aiConversations()->create(['locale' => 'en']);
        $conversation->forceFill(['last_message_at' => now()->subDays(120)])->save();
        $cached = $this->makeCachedAnswer($proposal, $conversation->id, now());

        app(AiConversationPruner::class)->pruneOlderThanDays(90);

        $this->assertModelMissing($conversation);
        $this->assertModelMissing($cached);
    }

    private function makeCachedAnswer(Proposal $proposal, ?int $conversationId, $createdAt, $usedAt = null): AiChatbotAnswer
    {
        $answer = AiChatbotAnswer::query()->create([
            'proposal_id' => $proposal->id,
            'ai_conversation_id' => $conversationId,
            'locale' => 'en',
            'question_hash' => hash('sha256', 'question-'.uniqid()),
            'prompt_hash' => hash('sha256', 'prompt'),
            'content' => 'Stored answer',
            'hits' => 1,
            'last_used_at' => $usedAt,
        ]);

        $answer->forceFill(['created_at' => $createdAt])->save();

        return $answer;
    }

    private function ask(Proposal $proposal, string $question, ?string $conversation = null): TestResponse
    {
        return $this->withDocumentAccess($proposal)->postJson(
            route('proposal.chat', ['slug' => $proposal->slug]),
            array_filter([
                'message' => $question,
                'conversation' => $conversation,
            ]),
        )->assertOk();
    }

    private function withDocumentAccess(Proposal $proposal): static
    {
        $this->withoutVite();

        return $this->withSession([
            DocumentAccessMiddleware::sessionKey('proposal', $proposal->id) => true,
            DocumentAccessMiddleware::versionKey('proposal', $proposal->id) => DocumentAccessMiddleware::credentialVersion($proposal),
        ]);
    }

    private function createProposal(array $overrides = []): Proposal
    {
        $userId = DB::table('users')->where('email', 'admin@example.test')->value('id')
            ?? DB::table('users')->insertGetId([
                'name' => 'Test Admin',
                'email' => 'admin@example.test',
                'password' => 'not-used-by-this-test',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $company = Company::query()->firstOrCreate(['email_1' => 'hello@example.test'], [
            'company_name' => 'PT Example Agency',
            'brand_name' => 'Example Agency',
            'address' => 'Jakarta',
            'phone_1' => '08123456789',
            'tax_id' => 'NPWP-001',
            'default_currency' => 'IDR',
            'color_primary' => '#164e63',
            'color_secondary' => '#0f766e',
            'footer_text' => ['en' => 'Example footer', 'id' => 'Contoh footer'],
            'bank' => [],
            'pic' => [],
        ]);

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
            'user_id' => $userId,
            'company_id' => $company->id,
            'notes' => [],
        ], $overrides))->refresh();
    }
}
