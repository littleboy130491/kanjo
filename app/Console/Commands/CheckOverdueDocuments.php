<?php

namespace App\Console\Commands;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Proposal;
use Illuminate\Console\Command;

class CheckOverdueDocuments extends Command
{
    protected $signature = 'documents:check-overdue';

    protected $description = 'Expire overdue proposals and flag overdue invoices.';

    public function handle(): int
    {
        $today = today();

        $expiredProposals = Proposal::query()
            ->where('status', DocumentStatus::PUBLISHED)
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '<', $today)
            ->update([
                'status' => DocumentStatus::DRAFT,
            ]);

        $flaggedInvoices = Invoice::query()
            ->where('status', DocumentStatus::PUBLISHED)
            ->whereIn('payment_status', [
                PaymentStatus::UNPAID,
                PaymentStatus::PARTIALLY_PAID,
            ])
            ->whereDate('due_date', '<', $today)
            ->update([
                'payment_status' => PaymentStatus::OVERDUE,
            ]);

        $this->info("Expired proposals: {$expiredProposals}");
        $this->info("Flagged overdue invoices: {$flaggedInvoices}");
        $this->info('Done.');

        return self::SUCCESS;
    }
}
