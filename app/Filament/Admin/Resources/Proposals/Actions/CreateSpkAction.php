<?php

namespace App\Filament\Admin\Resources\Proposals\Actions;

use App\Enums\DocumentStatus;
use App\Filament\Admin\Resources\Spks\SpkResource;
use App\Models\Proposal;
use App\Models\Spk;
use App\Services\SpkTemplateRenderer;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;

class CreateSpkAction
{
    public static function make(bool $asLink = false): Action
    {
        $action = Action::make('create_spk')
            ->label('Create SPK')
            ->icon('heroicon-o-clipboard-document-list')
            ->schema(fn (Proposal $record): array => [
                Select::make('company_pic_index')
                    ->label('Company PIC')
                    ->options(self::companyPicOptions($record))
                    ->placeholder('Select company PIC')
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) use ($record): void {
                        $pic = self::companyPicAt($record, $state);

                        $set('company_pic_name', (string) data_get($pic, 'pic_name', ''));
                        $set('company_pic_role', (string) data_get($pic, 'pic_role', ''));
                    }),
                TextInput::make('company_pic_name')
                    ->label('PIC Name')
                    ->default(fn (Proposal $record): string => (string) data_get(self::companyPicAt($record, 0), 'pic_name', ''))
                    ->required()
                    ->maxLength(255),
                TextInput::make('company_pic_role')
                    ->label('PIC Role')
                    ->default(fn (Proposal $record): string => (string) data_get(self::companyPicAt($record, 0), 'pic_role', ''))
                    ->maxLength(255),
            ])
            ->action(function (Proposal $record, array $data) {
                $spk = self::createSpkFromProposal($record, $data);

                return redirect(SpkResource::getUrl('edit', ['record' => $spk]));
            });

        if ($asLink) {
            $action->link();
        }

        return $action;
    }

    public static function createSpkFromProposal(Proposal $proposal, array $data): Spk
    {
        $proposal->loadMissing('company');
        $company = $proposal->company;

        $spk = Spk::query()->create([
            'spk_date' => now()->toDateString(),
            'client_company' => (string) $proposal->client_company,
            'client_pic_name' => (string) $proposal->client_name,
            'client_pic_role' => '',
            'client_address' => $proposal->client_address,
            'company_name' => (string) ($company?->company_name ?? ''),
            'company_pic_name' => (string) ($data['company_pic_name'] ?? ''),
            'company_pic_role' => (string) ($data['company_pic_role'] ?? ''),
            'company_address' => $company?->address,
            'status' => DocumentStatus::PUBLISHED,
            'access_username' => $proposal->access_username,
            'access_password' => $proposal->access_password,
            'proposal_id' => $proposal->id,
            'client_id' => $proposal->client_id,
            'company_id' => $proposal->company_id,
            'user_id' => auth()->id() ?? $proposal->user_id,
            'notes' => [],
        ]);

        SpkTemplateRenderer::renderDefaultsForRecord($spk, $proposal);
        $spk->saveQuietly();

        return $spk;
    }

    /**
     * @return array<string, string>
     */
    private static function companyPicOptions(Proposal $proposal): array
    {
        $proposal->loadMissing('company');

        return collect($proposal->company?->pic ?? [])
            ->mapWithKeys(fn (array $pic, int $index): array => [
                (string) $index => (string) (data_get($pic, 'pic_name') ?: 'PIC #'.($index + 1)),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private static function companyPicAt(Proposal $proposal, mixed $index): array
    {
        $proposal->loadMissing('company');
        $pics = $proposal->company?->pic ?? [];
        $index = filled($index) ? (int) $index : 0;
        $pic = $pics[$index] ?? [];

        return is_array($pic) ? $pic : [];
    }
}
