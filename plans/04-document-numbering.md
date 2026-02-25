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

## Database Fields (per document)

| Field | Type | Notes |
|---|---|---|
| `document_number` | string | Full formatted string (display/search) |
| `document_number_raw` | integer | The auto-increment portion |
| `document_number_suffix` | string | Default `NEW` |
| `document_number_override` | boolean | Flag to allow admin override |

---

## Implementation

### Service Class: `DocumentNumberGenerator`

**File:** `app/Services/DocumentNumberGenerator.php`

#### Methods:

**`generate(string $type, Carbon $date, string $suffix = 'NEW'): array`**

Returns:
```php
[
    'document_number'        => 'QUO/001/IV/26/NEW',
    'document_number_raw'    => 1,
    'document_number_suffix' => 'NEW',
]
```

**Logic:**
1. Determine the target table based on `$type` (`QUO` → `proposals`, `INV` → `invoices`).
2. Query the table for the same month and year of `$date` — take `MAX(document_number_raw) + 1`, default to `1`.
3. Convert month integer to Roman numeral using a static lookup array.
4. Format: `sprintf('%s/%03d/%s/%02d/%s', $type, $raw, $roman, $yy, $suffix)`
5. Wrap in a DB transaction with row locking to prevent race conditions.

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

Wire into model creation via an **Eloquent `creating` event** in the model's `booted()` method:

```php
// In Proposal model booted()
static::creating(function (Proposal $proposal) {
    if (!$proposal->document_number_override) {
        $data = DocumentNumberGenerator::generate('QUO', $proposal->issue_date ?? now());
        $proposal->document_number        = $data['document_number'];
        $proposal->document_number_raw    = $data['document_number_raw'];
        $proposal->document_number_suffix = $data['document_number_suffix'];
    }
});
```

Same pattern for Invoice with type `INV`.

---

### Admin Override
When `document_number_override` is `true`:
- Admin sets `document_number_suffix` manually (e.g., `REV`)
- The full `document_number` is recalculated using the existing `document_number_raw` but with the new suffix
- Do NOT regenerate the raw number

---

## Unique Constraint

**PRD spec:** Compound unique on `type + raw_number + month + year` to prevent duplicates within a month.

Since proposals and invoices are in separate tables, each table inherently separates by type. Enforce uniqueness within a table using:
- DB transaction with `lockForUpdate()` in `DocumentNumberGenerator` to prevent race conditions
- App-level validation before saving

---

## Acceptance Criteria
- Creating 3 proposals in April 2026 produces: `QUO/001/IV/26/NEW`, `QUO/002/IV/26/NEW`, `QUO/003/IV/26/NEW`
- Creating a proposal in May 2026 resets to `QUO/001/V/26/NEW`
- Setting override + suffix `REV` on `QUO/002/IV/26/NEW` produces `QUO/002/IV/26/REV`
- Concurrent creation does not produce duplicate numbers
