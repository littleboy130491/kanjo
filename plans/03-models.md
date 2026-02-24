# Activity 03 — Eloquent Models

## Goal
Create `Company`, `Proposal`, and `Invoice` models with correct casts, translatable fields, relationships, and soft deletes.

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
  - `tax_rate`, `tax_amount`, `total_amount` → `decimal:2`
  - `offer_1_price`, etc. → `decimal:2`
- Relationships:
  - `belongsTo(Company::class)`
  - `belongsTo(User::class)`
  - `hasMany(Invoice::class)`

```php
protected $translatable = [
    'brief', 'core_services', 'features', 'server', 'assets',
    'security', 'support', 'additional_benefit', 'add_on',
    'payment', 'terms_condition',
    'offer_1_project_timeline', 'offer_2_project_timeline',
];

protected $casts = [
    'portfolios'               => 'array',
    'document_number_override' => 'boolean',
    'issue_date'               => 'date',
    'valid_until'              => 'date',
    'tax_rate'                 => 'decimal:2',
    'tax_amount'               => 'decimal:2',
    'total_amount'             => 'decimal:2',
    'offer_1_price'            => 'decimal:2',
    'offer_1_original_price'   => 'decimal:2',
    'offer_1_renewal_price'    => 'decimal:2',
    'offer_2_price'            => 'decimal:2',
    'offer_2_original_price'   => 'decimal:2',
    'offer_2_renewal_price'    => 'decimal:2',
];
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
  - `tax_rate`, `tax_amount`, `subtotal`, `total`, `paid_amount` → `decimal:2`
- Relationships:
  - `belongsTo(Proposal::class)`
  - `belongsTo(Company::class)`
  - `belongsTo(User::class)`

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
- Models load without errors
- `Proposal::with('company', 'invoices')` works
- `$proposal->getTranslation('features', 'id')` returns Indonesian values
- Soft deletes work: `Proposal::withTrashed()` includes deleted records
