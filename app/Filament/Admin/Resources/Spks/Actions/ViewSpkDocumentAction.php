<?php

namespace App\Filament\Admin\Resources\Spks\Actions;

use App\Models\Spk;
use Filament\Actions\Action;

class ViewSpkDocumentAction
{
    public static function make(bool $asLink = false): Action
    {
        $action = Action::make('view_document')
            ->label('View Document')
            ->icon('heroicon-o-eye')
            ->url(fn (Spk $record): string => route('spk.show', [
                'slug' => $record->slug ?: str_replace('/', '-', $record->document_number),
            ]))
            ->openUrlInNewTab();

        if ($asLink) {
            $action->link();
        }

        return $action;
    }
}
