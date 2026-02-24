# Activity 04 — Document Numbering System

## Goal
Implement the auto-generated document number logic used by both Proposals and Invoices.

---

## Format
```
{TYPE}/{NUM}/{ROMAN_MONTH}/{YY}/{SUFFIX}
```

| Segment      | Description                              |
|--------------|------------------------------------------|
| TYPE         | `QUO` for proposals, `INV` for invoices  |
| NUM          | Auto-increment integer, resets monthly   |
| ROMAN_MONTH  | Month in Roman numerals (I–XII)          |
| YY           | Last two digits of year                  |
| SUFFIX       | Default `NEW`, admin can override        |

**Examples:**
- `QUO/001/IV/26/NEW`
- `INV/003/IV/26/REV`

---

## Implementation

### Service Class: `DocumentNumberService`

**File:** `app/Services/DocumentNumberService.php`

#### Methods:

**`generate(string $type, Carbon $date, string $suffix = 'NEW'): array`**

Returns:
```php
[
    'document_number'     => 'QUO/001/IV/26/NEW',
    'document_number_raw' => 1,
    'document_number_suffix' => 'NEW',
]
```

**Logic:**
1. Determine the next `document_number_raw` by querying the same table for the same `type`, same month, and same year — take `MAX(document_number_raw) + 1`, default to `1`.
2. Convert month integer to Roman numeral using a static lookup array.
3. Format: `sprintf('%s/%03d/%s/%02d/%s', $type, $raw, $roman, $yy, $suffix)`
4. Wrap in a DB transaction to prevent race conditions.

**`toRoman(int $month): string`**

Static method. Lookup table:
```php
private static array $roman = [
    1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
    5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
    9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
];
```

---

### Integration

Wire into model creation via an **Eloquent `creating` observer** or **service call in Filament form**:

```php
// In Proposal::creating observer
$data = DocumentNumberService::generate('QUO', now());
$proposal->document_number        = $data['document_number'];
$proposal->document_number_raw    = $data['document_number_raw'];
$proposal->document_number_suffix = $data['document_number_suffix'];
```

For `document_number_override = true`, skip auto-generation and allow admin to input suffix manually (only the suffix changes, not the full number).

---

### Admin Override
When `document_number_override` is `true`:
- Admin sets `document_number_suffix` manually (e.g., `REV`)
- The full `document_number` is recalculated using the existing `document_number_raw` but with the new suffix
- Do NOT regenerate the raw number

---

## Database Unique Constraint
Add a compound unique index in the migration to prevent duplicates:
```php
// For proposals table
$table->unique(['document_number_raw', DB::raw('MONTH(issue_date)'), DB::raw('YEAR(issue_date)')]);
```
> Implement as a composite unique index at the app layer if DB-level computed columns are unavailable.

---

## Acceptance Criteria
- Creating 3 proposals in April 2026 produces: `QUO/001/IV/26/NEW`, `QUO/002/IV/26/NEW`, `QUO/003/IV/26/NEW`
- Creating a proposal in May 2026 resets to `QUO/001/V/26/NEW`
- Setting override + suffix `REV` on `QUO/002/IV/26/NEW` produces `QUO/002/IV/26/REV`
- Concurrent creation does not produce duplicate numbers
