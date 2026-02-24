# Activity 12 — Filament Dashboard Widgets

## Goal
Build 4 stats widgets on the Filament admin dashboard.

---

## Widget 1: Total Outstanding

**Label:** Total Outstanding
**Description:** Sum of `total` for all published invoices with `unpaid` or `partially_paid` status

**Query:**
```php
Invoice::query()
    ->where('status', 'published')
    ->whereIn('payment_status', ['unpaid', 'partially_paid'])
    ->sum('total');
```

**Display:** Formatted as currency (IDR or mixed if multi-currency)

---

## Widget 2: Overdue Invoices

**Label:** Overdue Invoices
**Description:** Count of published invoices where `payment_status = 'overdue'`

**Query:**
```php
Invoice::query()
    ->where('status', 'published')
    ->where('payment_status', 'overdue')
    ->count();
```

**Display:** Integer count with danger (red) color indicator

---

## Widget 3: Pending Proposals

**Label:** Pending Proposals
**Description:** Count of published proposals not yet converted to any invoice

**Query:**
```php
Proposal::query()
    ->where('status', 'published')
    ->whereDoesntHave('invoices')
    ->count();
```

**Display:** Integer count with warning (yellow) color indicator

---

## Widget 4: Revenue This Month

**Label:** Revenue This Month
**Description:** Sum of `paid_amount` for invoices where `payment_status = 'paid'` and `paid_at` is within the current calendar month

**Query:**
```php
Invoice::query()
    ->where('payment_status', 'paid')
    ->whereMonth('paid_at', now()->month)
    ->whereYear('paid_at', now()->year)
    ->sum('paid_amount');
```

**Display:** Formatted as currency, success (green) color

---

## Implementation

Use Filament's `StatsOverviewWidget` with `Stat` components:

**File:** `app/Filament/Widgets/DocumentStatsWidget.php`

```php
class DocumentStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Outstanding', /* computed */),
            Stat::make('Overdue Invoices', /* computed */)->color('danger'),
            Stat::make('Pending Proposals', /* computed */)->color('warning'),
            Stat::make('Revenue This Month', /* computed */)->color('success'),
        ];
    }
}
```

Register in the Filament panel provider:
```php
->widgets([
    DocumentStatsWidget::class,
])
```

---

## Acceptance Criteria
- All 4 widgets appear on the Filament dashboard
- Values update based on real database state
- Currency values are formatted (e.g., `Rp 15.000.000`)
- Overdue widget shows red color indicator
- Pending proposals excludes proposals that have at least one linked invoice
