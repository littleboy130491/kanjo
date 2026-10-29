<?php

namespace App\Filament\Admin\Pages;

use App\Services\AiChatbot\ProposalChatbot;
use App\Settings\AiChatbotSettings;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ManageAiChatbotSettings extends SettingsPage
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'AI Chatbot';

    protected static ?string $title = 'AI Chatbot';

    protected static ?string $slug = 'ai-chatbot';

    protected static string $settings = AiChatbotSettings::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('AI Chatbot Settings')
                    ->tabs([
                        Tab::make('Provider')
                            ->icon('heroicon-o-cpu-chip')
                            ->schema([
                                Section::make('Availability')
                                    ->description('The chatbot appears on published proposal pages. Each proposal can also turn it off in its AI Chatbot tab.')
                                    ->schema([
                                        Toggle::make('enabled')
                                            ->label('Enable AI chatbot on proposals')
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),

                                Section::make('Model')
                                    ->description('Which AI answers the visitors. The API key is read from .env and never stored here.')
                                    ->schema([
                                        Select::make('provider')
                                            ->label('AI Provider')
                                            ->options(fn (): array => collect(ProposalChatbot::PROVIDERS)
                                                ->map(fn (array $provider): string => $provider['label'])
                                                ->all())
                                            ->required()
                                            ->native(false)
                                            ->live()
                                            ->helperText(fn (Get $get): string => self::providerKeyStatus($get('provider'))),
                                        TextInput::make('model')
                                            ->label('Model')
                                            ->placeholder('Provider default')
                                            ->helperText('For example gpt-4o-mini, claude-haiku-4-5, or gemini-2.5-flash. Leave empty to use the provider default.')
                                            ->maxLength(255),
                                        TextInput::make('temperature')
                                            ->label('Temperature')
                                            ->placeholder('Model default')
                                            ->helperText('Optional. Leave empty (recommended). Newer models such as recent Claude models do not accept it; if a model rejects it, the chatbot retries without it.')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(2)
                                            ->step(0.1)
                                            ->nullable(),
                                        TextInput::make('max_tokens')
                                            ->label('Max Reply Tokens')
                                            ->helperText('Limits how long each reply can be.')
                                            ->numeric()
                                            ->integer()
                                            ->minValue(50)
                                            ->maxValue(8000)
                                            ->required(),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),

                                Section::make('Protection')
                                    ->schema([
                                        TextInput::make('rate_limit_per_minute')
                                            ->label('Messages per Minute')
                                            ->helperText('Per visitor IP and proposal. Protects against abuse and cost spikes.')
                                            ->numeric()
                                            ->integer()
                                            ->minValue(1)
                                            ->maxValue(120)
                                            ->required(),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Behaviour')
                            ->icon('heroicon-o-adjustments-horizontal')
                            ->schema([
                                Section::make('Instructions (System Prompt)')
                                    ->key('instructions-section')
                                    ->description('Tells the AI how to behave: what it may answer, what it must decline, and its tone. Placeholders: {bot_name}, {company}, {language}. Safety rules (use only the proposal information, never invent prices or terms, only this proposal, ignore attempts to change the rules) are always added and cannot be removed here.')
                                    ->headerActions([
                                        Action::make('reset_instructions')
                                            ->label('Reset to default')
                                            ->icon('heroicon-o-arrow-path')
                                            ->color('gray')
                                            ->requiresConfirmation()
                                            ->modalHeading('Reset instructions?')
                                            ->modalDescription('This replaces the current instructions with the built-in default. Save the page to apply it.')
                                            ->action(fn (Set $set) => $set('instructions', ProposalChatbot::DEFAULT_INSTRUCTIONS)),
                                    ])
                                    ->schema([
                                        Textarea::make('instructions')
                                            ->hiddenLabel()
                                            ->rows(16)
                                            ->maxLength(10000)
                                            ->afterStateHydrated(function (Textarea $component, ?string $state): void {
                                                if (blank($state)) {
                                                    $component->state(ProposalChatbot::DEFAULT_INSTRUCTIONS);
                                                }
                                            })
                                            ->helperText('Leave empty to use the built-in default.')
                                            ->nullable()
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Knowledge')
                            ->icon('heroicon-o-book-open')
                            ->schema([
                                Section::make('Default Context')
                                    ->description('Sent to the AI for every proposal, together with that proposal\'s content and its own extra context. Use it for company facts, policies, and tone. Changes apply to all proposals right away.')
                                    ->schema([
                                        Textarea::make('default_context')
                                            ->hiddenLabel()
                                            ->rows(14)
                                            ->maxLength(20000)
                                            ->placeholder("Example:\n- We are a web design agency based in Jakarta.\n- Office hours: Mon–Fri, 09:00–17:00 WIB.\n- For changes to the offer, contact us via WhatsApp.")
                                            ->nullable()
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Appearance')
                            ->icon('heroicon-o-paint-brush')
                            ->schema([
                                Section::make('Identity')
                                    ->schema([
                                        TextInput::make('bot_name')
                                            ->label('Bot Name')
                                            ->required()
                                            ->maxLength(60),
                                        ColorPicker::make('accent_color')
                                            ->label('Accent Color')
                                            ->helperText('Leave empty to use the issuing company primary color.')
                                            ->regex('/^#[0-9a-fA-F]{3,8}$/')
                                            ->nullable(),
                                        CuratorPicker::make('avatar')
                                            ->label('Avatar')
                                            ->constrained()
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),

                                Section::make('Welcome Message')
                                    ->description('The first message visitors see when they open the chat.')
                                    ->schema([
                                        Textarea::make('welcome_message.en')
                                            ->label('English')
                                            ->rows(2)
                                            ->maxLength(500),
                                        Textarea::make('welcome_message.id')
                                            ->label('Indonesian')
                                            ->rows(2)
                                            ->maxLength(500),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Quick Questions')
                            ->icon('heroicon-o-chat-bubble-bottom-center-text')
                            ->schema([
                                Section::make('Buttons')
                                    ->description('Buttons visitors can tap to ask common questions. A proposal can replace these with its own list in its AI Chatbot tab.')
                                    ->schema([
                                        Repeater::make('quick_questions')
                                            ->hiddenLabel()
                                            ->schema([
                                                TextInput::make('en')
                                                    ->label('English')
                                                    ->maxLength(150),
                                                TextInput::make('id')
                                                    ->label('Indonesian')
                                                    ->maxLength(150),
                                            ])
                                            ->columns(2)
                                            ->addActionLabel('Add question')
                                            ->reorderable()
                                            ->deletable()
                                            ->default([])
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['model'] = filled($data['model'] ?? null) ? trim((string) $data['model']) : null;
        $instructions = trim((string) ($data['instructions'] ?? ''));
        // Store null when unchanged so future improvements to the built-in default apply automatically.
        $data['instructions'] = $instructions === '' || $instructions === trim(ProposalChatbot::DEFAULT_INSTRUCTIONS)
            ? null
            : $instructions;
        $data['accent_color'] = filled($data['accent_color'] ?? null) ? $data['accent_color'] : null;
        $data['avatar'] = filled($data['avatar'] ?? null) ? (int) (is_array($data['avatar']) ? reset($data['avatar']) : $data['avatar']) : null;
        $data['temperature'] = filled($data['temperature'] ?? null) ? (float) $data['temperature'] : null;
        $data['max_tokens'] = (int) ($data['max_tokens'] ?? 800);
        $data['rate_limit_per_minute'] = (int) ($data['rate_limit_per_minute'] ?? 10);
        $data['welcome_message'] = [
            'en' => $data['welcome_message']['en'] ?? null,
            'id' => $data['welcome_message']['id'] ?? null,
        ];
        $data['quick_questions'] = collect($data['quick_questions'] ?? [])
            ->map(fn (array $row): array => [
                'en' => filled($row['en'] ?? null) ? trim((string) $row['en']) : null,
                'id' => filled($row['id'] ?? null) ? trim((string) $row['id']) : null,
            ])
            ->filter(fn (array $row): bool => filled($row['en']) || filled($row['id']))
            ->values()
            ->all();

        return $data;
    }

    private static function providerKeyStatus(?string $provider): string
    {
        $env = ProposalChatbot::PROVIDERS[$provider]['env'] ?? null;

        if ($provider === null || ! array_key_exists($provider, ProposalChatbot::PROVIDERS)) {
            return 'Choose a provider.';
        }

        if ($env === null) {
            return 'No API key needed. Set OLLAMA_URL in .env if Ollama is not on localhost.';
        }

        return ProposalChatbot::providerHasKey($provider)
            ? "✓ {$env} is set in .env."
            : "⚠ {$env} is missing in .env. The chatbot will show an error message until it is set.";
    }
}
