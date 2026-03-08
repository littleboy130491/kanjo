<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About This Project

This is a quotation and invoice management web app for a web design agency with multiple brands. Built with Laravel 12 and Filament 5.

## Features

- Multi-brand company management
- Client management with service tracking
- Proposal creation and management
- Invoice generation with payment tracking
- Document access for clients via username/password
- PDF generation for proposals and invoices
- Dashboard with revenue and outstanding analytics

## Tech Stack

- **Framework**: Laravel 12
- **Admin Panel**: Filament 5
- **CSS**: Tailwind CSS
- **Translations**: Spatie Laravel Translatable (EN/ID)
- **Media**: Curator (Filament plugin)
- **PDF**: Browsershot

## Installation

```bash
# Clone the repository
git clone https://github.com/littleboy130491/kanjo.git

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Copy environment file
copy .env from .env link

# Run migrations
php artisan migrate

# Seed the database
php artisan db:seed

```

## Default Login

- **URL**: `/admin`
- **Username**: `admin@example.com`
- **Password**: `password`

### Create admin user

```bash
php artisan make:filament-user

```

## Development

```bash
# Run development server
npm run dev

# Build for production
npm run build
```

## Resource Locking

Proposal and invoice edit pages use a timeout-based resource lock system to reduce concurrent edits.

### How it works

- Opening an edit page creates or refreshes a lock for that record.
- While the edit page stays open, the browser refreshes the lock every 15 seconds.
- Saving or cancelling releases the lock immediately.
- If a user closes the tab or leaves the page without saving, the lock is not deleted immediately. It expires after 300 seconds if no keep-alive request refreshes it.
- If another user takes over the lock, the new lock row belongs to that user. The previous editor is notified on the next poll and redirected back to the list page.
- Proposal and invoice list pages also refresh every 15 seconds, so the table lock message updates without a full reload.

### Main files

- Lock storage and ownership:
  - `database/migrations/2026_03_08_140201_create_resource_locks_table.php`
  - `app/Models/ResourceLock.php`
  - `app/Models/Concerns/HasLocks.php`
- Edit-page lock behavior:
  - `app/Filament/Admin/Resources/Concerns/UsesResourceLock.php`
- Shared polling:
  - `app/Filament/Admin/Resources/Concerns/UsesPolling.php`
- Auto-refreshing list pages:
  - `app/Filament/Admin/Resources/Concerns/RefreshesListRecords.php`
- Current resource integrations:
  - `app/Models/Proposal.php`
  - `app/Models/Invoice.php`
  - `app/Filament/Admin/Resources/Proposals/Pages/EditProposal.php`
  - `app/Filament/Admin/Resources/Invoices/Pages/EditInvoice.php`
  - `app/Filament/Admin/Resources/Proposals/Pages/ListProposals.php`
  - `app/Filament/Admin/Resources/Invoices/Pages/ListInvoices.php`
  - `app/Filament/Admin/Resources/Proposals/Tables/ProposalsTable.php`
  - `app/Filament/Admin/Resources/Invoices/Tables/InvoicesTable.php`
  - `app/Filament/Admin/Resources/Proposals/ProposalResource.php`
  - `app/Filament/Admin/Resources/Invoices/InvoiceResource.php`

### Add lock support to a new resource

1. Add `HasLocks` to the model.
2. Add `UsesResourceLock` to the resource edit page.
3. Merge lock actions into the edit page header actions with `$this->mergeLockActions([...])`.
4. Eager-load `resourceLock.user` in the resource query.
5. Add a table description under the main identifying column.
6. Add `RefreshesListRecords` to the list page if you want the table to auto-update.

Minimal model example:

```php
use App\Models\Concerns\HasLocks;

class Client extends Model
{
    use HasLocks;
}
```

Minimal edit page example:

```php
use App\Filament\Admin\Resources\Concerns\UsesResourceLock;
use Filament\Resources\Pages\EditRecord;

class EditClient extends EditRecord
{
    use UsesResourceLock;

    protected function getHeaderActions(): array
    {
        return $this->mergeLockActions([
            // existing actions...
        ]);
    }
}
```

Minimal resource query example:

```php
use Illuminate\Database\Eloquent\Builder;

public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()->with(['resourceLock.user']);
}
```

Minimal table description example:

```php
use App\Models\Client;
use Filament\Tables\Columns\TextColumn;

TextColumn::make('name')
    ->searchable()
    ->sortable()
    ->description(fn (Client $record): ?string => $record->resourceLock?->isActive()
        ? (($record->resourceLock->user?->name ?? 'Someone') . ' is editing this record')
        : null)
```

Minimal list page auto-refresh example:

```php
use App\Filament\Admin\Resources\Concerns\RefreshesListRecords;
use Filament\Resources\Pages\ListRecords;

class ListClients extends ListRecords
{
    use RefreshesListRecords;
}
```

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
