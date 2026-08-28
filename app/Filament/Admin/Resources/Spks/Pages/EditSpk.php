<?php

namespace App\Filament\Admin\Resources\Spks\Pages;

use App\Filament\Admin\Resources\Concerns\UsesResourceLock;
use App\Filament\Admin\Resources\Spks\Actions\DownloadSpkPdfAction;
use App\Filament\Admin\Resources\Spks\Actions\DuplicateSpkAction;
use App\Filament\Admin\Resources\Spks\Actions\ViewProposalAction;
use App\Filament\Admin\Resources\Spks\Actions\ViewSpkDocumentAction;
use App\Filament\Admin\Resources\Spks\SpkResource;
use App\Models\Spk;
use App\Services\SpkTemplateRenderer;
use App\Support\RichTextHtmlNormalizer;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditSpk extends EditRecord
{
    use UsesResourceLock;

    protected static string $resource = SpkResource::class;

    protected function getHeaderActions(): array
    {
        return $this->mergeLockActions([
            Action::make('save')
                ->label('Save')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->link()
                ->action('save'),
            DuplicateSpkAction::make(name: 'duplicate', asLink: true),
            ViewSpkDocumentAction::make(asLink: true),
            DownloadSpkPdfAction::make(name: 'create_pdf', label: 'Create PDF', asLink: true)
                ->icon('heroicon-o-document-arrow-down'),
            ViewProposalAction::make(asLink: true),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Spk $record */
        $record = $this->getRecord();

        foreach (['title', 'party_identification'] as $field) {
            if (! isset($data[$field]) || ! is_array($data[$field])) {
                $data[$field] = [];
            }

            foreach (config('translatable.locales', ['en', 'id']) as $locale) {
                $stored = is_string($data[$field][$locale] ?? null) ? $data[$field][$locale] : '';

                if (RichTextHtmlNormalizer::isBlankHtml($stored)) {
                    $data[$field][$locale] = SpkTemplateRenderer::generatedHtml($field, $record, $locale);
                }
            }
        }

        return $data;
    }
}
