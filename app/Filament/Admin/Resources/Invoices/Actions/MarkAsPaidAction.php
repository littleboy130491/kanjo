<?php

namespace App\Filament\Admin\Resources\Invoices\Actions;

use App\Enums\PaymentStatus;
use App\Models\Invoice;
use Filament\Actions\Action;

class MarkAsPaidAction
{
    public static function make(): Action
    {
        return Action::make('mark_as_paid')
            ->label('Mark as Paid')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->visible(fn (Invoice $record): bool => in_array($record->payment_status, [
                PaymentStatus::UNPAID,
                PaymentStatus::PARTIALLY_PAID,
                PaymentStatus::OVERDUE,
            ], true))
            ->requiresConfirmation()
            ->action(fn (Invoice $record) => $record->update([
                'payment_status' => PaymentStatus::PAID,
                'paid_at' => now(),
            ]));
    }
}
