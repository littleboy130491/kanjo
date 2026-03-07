<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Proposal;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DocumentStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalOutstanding = Invoice::query()
            ->where('status', DocumentStatus::PUBLISHED)
            ->whereIn('payment_status', [
                PaymentStatus::UNPAID,
                PaymentStatus::PARTIALLY_PAID,
            ])
            ->sum('total');

        $overdueInvoices = Invoice::query()
            ->where('status', DocumentStatus::PUBLISHED)
            ->where('payment_status', PaymentStatus::OVERDUE)
            ->count();

        $pendingProposals = Proposal::query()
            ->where('status', DocumentStatus::PUBLISHED)
            ->whereDoesntHave('invoices')
            ->count();

        $revenueThisMonth = Invoice::query()
            ->where('payment_status', PaymentStatus::PAID)
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('total');

        return [
            Stat::make('Total Outstanding', $this->formatIdr($totalOutstanding))
                ->description('Published invoices unpaid or partially paid'),
            Stat::make('Overdue Invoices', number_format($overdueInvoices))
                ->description('Published invoices with overdue payment status')
                ->color('danger'),
            Stat::make('Pending Proposals', number_format($pendingProposals))
                ->description('Published proposals without linked invoice')
                ->color('warning'),
            Stat::make('Revenue This Month', $this->formatIdr($revenueThisMonth))
                ->description('Paid amount received in current month')
                ->color('success'),
        ];
    }

    private function formatIdr(float|int|string $amount): string
    {
        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }
}
