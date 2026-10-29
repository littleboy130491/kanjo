<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Filament\Admin\Pages\ManageAiChatbotSettings;
use App\Filament\Admin\Resources\AiConversations\Pages\ListAiConversations;
use App\Filament\Admin\Resources\AiConversations\Pages\ViewAiConversation;
use App\Filament\Admin\Resources\Proposals\Pages\EditProposal;
use App\Filament\Admin\Resources\Proposals\RelationManagers\AiConversationsRelationManager;
use App\Models\AiConversation;
use App\Models\AiConversationMessage;
use App\Models\Company;
use App\Models\Proposal;
use App\Models\User;
use App\Services\AiChatbot\ProposalChatbot;
use App\Services\AiConversationPruner;
use App\Settings\AiChatbotSettings;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Database\Seeders\RoleAndPermissionSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AiChatbotAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['curator.glide_token' => 'testing-glide-token']);
        $this->seed(RoleAndPermissionSeeder::class);
        $this->admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($this->admin);
    }

    public function test_proposal_form_saves_ai_chatbot_fields(): void
    {
        $proposal = $this->makeProposal();

        Livewire::test(EditProposal::class, ['record' => $proposal->getRouteKey()])
            ->assertSee('AI Chatbot')
            ->fillForm([
                'ai_chatbot_enabled' => false,
                'ai_chatbot_context' => 'Client asked for a fast launch.',
                'ai_chatbot_quick_questions' => [
                    ['en' => 'When can we start?', 'id' => 'Kapan bisa mulai?'],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $proposal->refresh();
        $this->assertFalse($proposal->ai_chatbot_enabled);
        $this->assertSame('Client asked for a fast launch.', $proposal->ai_chatbot_context);
        $this->assertSame([['en' => 'When can we start?', 'id' => 'Kapan bisa mulai?']], array_values($proposal->ai_chatbot_quick_questions));
    }

    public function test_settings_page_saves_settings(): void
    {
        $permission = array_key_first(FilamentShield::getPages()[ManageAiChatbotSettings::class]['permissions']);
        $this->admin->givePermissionTo(Permission::findOrCreate($permission, 'web'));

        Livewire::test(ManageAiChatbotSettings::class)
            ->assertOk()
            ->fillForm([
                'enabled' => false,
                'provider' => 'anthropic',
                'model' => ' claude-haiku-4-5 ',
                'temperature' => 0.2,
                'max_tokens' => 500,
                'rate_limit_per_minute' => 5,
                'bot_name' => 'Kanjo Bot',
                'accent_color' => '#112233',
                'welcome_message' => ['en' => 'Hello', 'id' => 'Halo'],
                'default_context' => 'We are based in Jakarta.',
                'quick_questions' => [
                    ['en' => 'Price?', 'id' => 'Harga?'],
                    ['en' => '', 'id' => ''],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app(AiChatbotSettings::class)->refresh();

        $this->assertFalse($settings->enabled);
        $this->assertSame('anthropic', $settings->provider);
        $this->assertSame('claude-haiku-4-5', $settings->model);
        $this->assertSame(500, $settings->max_tokens);
        $this->assertSame('Kanjo Bot', $settings->bot_name);
        $this->assertSame(['en' => 'Hello', 'id' => 'Halo'], $settings->welcome_message);
        $this->assertSame('We are based in Jakarta.', $settings->default_context);
        $this->assertSame([['en' => 'Price?', 'id' => 'Harga?']], $settings->quick_questions);
        $this->assertNull($settings->instructions, 'unchanged default instructions are stored as null');
    }

    public function test_settings_page_prefills_saves_and_resets_instructions(): void
    {
        $permission = array_key_first(FilamentShield::getPages()[ManageAiChatbotSettings::class]['permissions']);
        $this->admin->givePermissionTo(Permission::findOrCreate($permission, 'web'));

        Livewire::test(ManageAiChatbotSettings::class)
            ->assertSchemaStateSet(['instructions' => ProposalChatbot::DEFAULT_INSTRUCTIONS])
            ->fillForm(['instructions' => 'Only answer about the website project.'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Only answer about the website project.', app(AiChatbotSettings::class)->refresh()->instructions);

        Livewire::test(ManageAiChatbotSettings::class)
            ->callAction(TestAction::make('reset_instructions')->schemaComponent('instructions-section'))
            ->assertSchemaStateSet(['instructions' => ProposalChatbot::DEFAULT_INSTRUCTIONS])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull(app(AiChatbotSettings::class)->refresh()->instructions);
    }

    public function test_conversation_list_view_and_relation_manager_render(): void
    {
        $proposal = $this->makeProposal();
        $conversation = $this->makeConversation($proposal);

        Livewire::test(ListAiConversations::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$conversation])
            ->assertSee('How long does it take?');

        Livewire::test(ViewAiConversation::class, ['record' => $conversation->getRouteKey()])
            ->assertOk()
            ->assertSee('How long does it take?')
            ->assertSee('About 30 days.')
            ->assertSee('Provider timeout');

        Livewire::test(AiConversationsRelationManager::class, [
            'ownerRecord' => $proposal,
            'pageClass' => EditProposal::class,
        ])
            ->assertOk()
            ->assertCanSeeTableRecords([$conversation]);
    }

    public function test_prune_deletes_old_conversations_and_their_messages(): void
    {
        $proposal = $this->makeProposal();
        $old = $this->makeConversation($proposal);
        $old->forceFill(['last_message_at' => now()->subDays(120)])->save();
        $recent = $this->makeConversation($proposal);

        $deleted = app(AiConversationPruner::class)->pruneOlderThanDays(90);

        $this->assertSame(1, $deleted);
        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
        $this->assertSame(0, AiConversationMessage::query()->where('ai_conversation_id', $old->id)->count());
    }

    private function makeConversation(Proposal $proposal): AiConversation
    {
        $conversation = $proposal->aiConversations()->create(['locale' => 'en', 'ip_address' => '127.0.0.1']);
        $conversation->messages()->create(['role' => 'user', 'content' => 'How long does it take?']);
        $conversation->messages()->create(['role' => 'assistant', 'content' => 'About 30 days.', 'provider' => 'openai', 'model' => 'gpt-4o-mini']);
        $conversation->messages()->create(['role' => 'user', 'content' => 'And the price?']);
        $conversation->messages()->create(['role' => 'assistant', 'content' => 'Sorry, try again.', 'error' => 'Provider timeout']);

        return $conversation->refresh();
    }

    private function makeProposal(): Proposal
    {
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
            'user_id' => $this->admin->id,
            'company_id' => $company->id,
            'notes' => [],
        ])->refresh();
    }
}
