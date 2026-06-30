<?php

namespace App\Filament\Admin\Resources\Spks\Actions;

use App\Enums\DocumentStatus;
use App\Filament\Admin\Resources\Spks\SpkResource;
use App\Models\Spk;
use Filament\Actions\Action;

class DuplicateSpkAction
{
    public static function make(
        string $name = 'duplicate_spk',
        string $label = 'Duplicate',
        bool $asLink = false,
    ): Action {
        $action = Action::make($name)
            ->label($label)
            ->icon('heroicon-o-document-duplicate')
            ->action(function (Spk $record) {
                $duplicate = self::duplicate($record);

                return redirect(SpkResource::getUrl('edit', ['record' => $duplicate]));
            });

        if ($asLink) {
            $action->link();
        }

        return $action;
    }

    public static function duplicate(Spk $record): Spk
    {
        $duplicate = $record->replicate([
            'document_number',
            'slug',
            'document_number_raw',
            'document_number_override',
            'issue_month',
            'issue_year',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);

        $duplicate->status = DocumentStatus::DRAFT;
        $duplicate->document_number = null;
        $duplicate->document_number_raw = null;
        $duplicate->issue_month = null;
        $duplicate->issue_year = null;
        $duplicate->document_number_override = false;
        $duplicate->spk_date = now()->toDateString();
        $duplicate->save();

        return $duplicate;
    }
}
