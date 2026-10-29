<?php

namespace App\Filament\Admin\Pages;

use App\Services\AiChatbot\ProposalChatbot;
use App\Settings\AiChatbotSettings;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
                Section::make('General')
                    ->description('The chatbot appears on published proposal pages. Each proposal can also turn it off in its AI Chatbot tab.')
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Enable AI chatbot on proposals')
                            ->columnSpanFull(),
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
                            ->helperText('Lower is more factual. 0.2 to 0.5 works well for proposals.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(2)
                            ->step(0.1)
                            ->required(),
                        TextInput::make('max_tokens')
                            ->label('Max Reply Tokens')
                            ->helperText('Limits how long each reply can be.')
                            ->numeric()
                            ->integer()
                            ->minValue(50)
                            ->maxValue(8000)
                            ->required(),
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

                Section::make('Branding')
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
                        Textarea::make('welcome_message.en')
                            ->label('Welcome Message (English)')
                            ->rows(2)
                            ->maxLength(500),
                        Textarea::make('welcome_message.id')
                            ->label('Welcome Message (Indonesian)')
                            ->rows(2)
                            ->maxLength(500),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Default Context')
                    ->description('Sent to the AI for every proposal, together with that proposal\'s content and its own extra context. Use it for company facts, policies, and tone. Changes apply to all proposals right away.')
                    ->schema([
                        Textarea::make('default_context')
                            ->hiddenLabel()
                            ->rows(12)
                            ->maxLength(20000)
                            ->placeholder("Example:\n- We are a web design agency based in Jakarta.\n- Office hours: Mon–Fri, 09:00–17:00 WIB.\n- For changes to the offer, contact us via WhatsApp.")
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('Quick Questions')
                    ->description('Buttons visitors can tap to ask common questions. A proposal can replace these with its own list.')
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
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['model'] = filled($data['model'] ?? null) ? trim((string) $data['model']) : null;
        $data['accent_color'] = filled($data['accent_color'] ?? null) ? $data['accent_color'] : null;
        $data['avatar'] = filled($data['avatar'] ?? null) ? (int) (is_array($data['avatar']) ? reset($data['avatar']) : $data['avatar']) : null;
        $data['temperature'] = (float) ($data['temperature'] ?? 0.3);
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
