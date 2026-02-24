# Activity 11 — Scheduled Artisan Command

## Goal
Implement `php artisan documents:check-overdue` to auto-expire stale proposals and flag overdue invoices on a daily schedule.

---

## Command

**File:** `app/Console/Commands/CheckOverdueDocuments.php`

**Signature:** `documents:check-overdue`

---

## Operation 1: Expire Overdue Proposals

**Condition:**
- `status = 'published'`
- `valid_until IS NOT NULL`
- `valid_until < today`

**Action:** Set `status` → `draft`

**Query:**
```php
Proposal::query()
    ->where('status', 'published')
    ->whereNotNull('valid_until')
    ->whereDate('valid_until', '<', today())
    ->update(['status' => 'draft']);
```

> Proposals with `valid_until = null` are **never** auto-expired (infinite validity).

---

## Operation 2: Flag Overdue Invoices

**Condition:**
- `status = 'published'`
- `payment_status IN ('unpaid', 'partially_paid')`
- `due_date < today`

**Action:** Set `payment_status` → `overdue`

**Query:**
```php
Invoice::query()
    ->where('status', 'published')
    ->whereIn('payment_status', ['unpaid', 'partially_paid'])
    ->whereDate('due_date', '<', today())
    ->update(['payment_status' => 'overdue']);
```

---

## Idempotency
Both operations use bulk `update()` with the exact matching conditions, so running the command multiple times per day produces no duplicate writes:
- Proposals already set to `draft` won't match `status = 'published'`
- Invoices already set to `overdue` won't match `payment_status IN ('unpaid', 'partially_paid')`

---

## Schedule Registration

**File:** `routes/console.php` (Laravel 12 style) or `app/Console/Kernel.php`

```php
Schedule::command('documents:check-overdue')->daily();
```

### Cron Setup (Production)
```
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

Laravel Sail handles this via `supervisord` or Docker cron configuration.

---

## Console Output
The command should output a summary:
```
Expired proposals: 3
Flagged overdue invoices: 7
Done.
```

Use `$this->info()` for output.

---

## Acceptance Criteria
- Running the command expires all published proposals past their `valid_until` date
- Running the command flags all published invoices past due date as `overdue`
- Proposals with `valid_until = null` are never touched
- Proposals/invoices already in the target state are not double-updated
- Command runs cleanly via `php artisan documents:check-overdue`
- Laravel scheduler runs it daily via cron
