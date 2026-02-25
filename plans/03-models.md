# Activity 03 — Eloquent Models

## Goal
Create `Company`, `Client`, `Service`, `Proposal`, and `Invoice` models with correct casts, translatable fields, relationships, and soft deletes.

---

## Model: `Company`

**File:** `app/Models/Company.php`

- Uses `HasTranslations` from Spatie
- Translatable fields: `footer_text`
- Casts: `bank` → array, `pic` → array
- Relationships:
  - `hasMany(Proposal::class)`
  - `hasMany(Invoice::class)`

```php
protected $translatable = ['footer_text'];

protected $casts = [
    'bank' => 'array',
    'pic'  => 'array',
];
```

---

## Model: `Client`

**File:** `app/Models/Client.php`

- Uses `SoftDeletes`
- Fields: `name`, `company`, `email`, `phone`, `notes`
- Casts: `notes` → array
- Relationships:
  - `hasMany(Proposal::class)`
  - `hasMany(Invoice::class)`
  - `hasMany(Service::class)`

```php
use Illuminate\Database\Eloquent\SoftDeletes;

protected $casts = [
    'notes' => 'array',
];
```

---

## Model: `Service`

**File:** `app/Models/Service.php`

- Uses `SoftDeletes`
- Fields: `name`, `domain`, `start_date`, `renewal_date`, `status`, `notes`, `client_id`
- Casts: `notes` → array, `status` → `ServiceStatus` enum
- Relationships:
  - `belongsTo(Client::class)`
  - `hasMany(Proposal::class)`
  - `hasMany(Invoice::class)`

```php
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\ServiceStatus;

protected $casts = [
    'notes'  => 'array',
    'status' => ServiceStatus::class,
];
```

### ServiceStatus Enum

**File:** `app/Enums/ServiceStatus.php`

```php
enum ServiceStatus: string
{
    case Terminated = 'terminated';
    case OnGoing = 'on-going';
    case Suspended = 'suspended';
}
```

---

## Model: `Proposal`

**File:** `app/Models/Proposal.php`

- Uses `HasTranslations`, `SoftDeletes`
- Translatable fields:
  - `brief`, `core_services`, `features`, `server`, `assets`, `security`, `support`
  - `additional_benefit`, `add_on`, `payment`, `terms_condition`
  - `offer_1_project_timeline`, `offer_2_project_timeline`
- Non-translatable JSON casts: `portfolios`
- Casts:
  - All translatable arrays → handled by Spatie (stored as JSON with locale keys)
  - `portfolios` → `array`
  - `document_number_override` → `boolean`
  - `issue_date`, `valid_until` → `date`
  - `status` → `DocumentStatus` enum
  - `tax_rate`, `tax_amount`, `total_amount` → `decimal:2`
  - `offer_1_price`, `offer_1_original_price`, `offer_1_renewal_price`, `offer_1_original_renewal_price` → `decimal:2`
  - `offer_2_price`, `offer_2_original_price`, `offer_2_renewal_price`, `offer_2_original_renewal_price` → `decimal:2`
- Relationships:
  - `belongsTo(Company::class)`
  - `belongsTo(User::class)`
  - `belongsTo(Client::class)` — nullable
  - `belongsTo(Service::class)` — nullable
  - `hasMany(Invoice::class)`

```php
protected $translatable = [
    'brief', 'core_services', 'features', 'server', 'assets',
    'security', 'support', 'additional_benefit', 'add_on',
    'payment', 'terms_condition',
    'offer_1_project_timeline', 'offer_2_project_timeline',
];

protected $casts = [
    'portfolios'                      => 'array',
    'document_number_override'        => 'boolean',
    'issue_date'                      => 'date',
    'valid_until'                     => 'date',
    'status'                          => DocumentStatus::class,
    'tax_rate'                        => 'decimal:2',
    'tax_amount'                      => 'decimal:2',
    'total_amount'                    => 'decimal:2',
    'offer_1_price'                   => 'decimal:2',
    'offer_1_original_price'          => 'decimal:2',
    'offer_1_renewal_price'           => 'decimal:2',
    'offer_1_original_renewal_price'  => 'decimal:2',
    'offer_2_price'                   => 'decimal:2',
    'offer_2_original_price'          => 'decimal:2',
    'offer_2_renewal_price'           => 'decimal:2',
    'offer_2_original_renewal_price'  => 'decimal:2',
];
```

### DocumentStatus Enum

**File:** `app/Enums/DocumentStatus.php`

```php
enum DocumentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
```

---

## Model: `Invoice`

**File:** `app/Models/Invoice.php`

- Uses `HasTranslations`, `SoftDeletes`
- Translatable fields: `items` (sub-fields `title`, `description` per locale)
- Casts:
  - `document_number_override` → `boolean`
  - `issue_date`, `due_date` → `date`
  - `paid_at` → `datetime`
  - `status` → `DocumentStatus` enum
  - `payment_status` → `PaymentStatus` enum
  - `tax_rate`, `tax_amount`, `subtotal`, `total`, `paid_amount` → `decimal:2`
- Relationships:
  - `belongsTo(Proposal::class)` — nullable
  - `belongsTo(Company::class)`
  - `belongsTo(User::class)`
  - `belongsTo(Client::class)` — nullable
  - `belongsTo(Service::class)` — nullable

### PaymentStatus Enum

**File:** `app/Enums/PaymentStatus.php`

```php
enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
}
```

---

## Translation Structure for JSON Arrays

Sub-field translation pattern (applied in all translatable JSON arrays):
```json
[
  {
    "feature_name": { "en": "Responsive Design", "id": "Desain Responsif" },
    "feature_description": { "en": "Adapts to all screen sizes", "id": "..." }
  }
]
```

The array length/order stays the same across locales — only text values differ.

---

## Acceptance Criteria
- All 5 models load without errors
- `Proposal::with('company', 'invoices', 'client', 'service')` works
- `Invoice::with('proposal', 'company', 'client', 'service')` works
- `Client::with('proposals', 'invoices', 'services')` works
- `Service::with('client', 'proposals', 'invoices')` works
- `$proposal->getTranslation('features', 'id')` returns Indonesian values
- Soft deletes work on Client, Service, Proposal, Invoice
- Enums cast correctly for `status`, `payment_status`, `ServiceStatus`
