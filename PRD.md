# Quotation & Invoice Web App — Product Requirements Document

## Tech Stack

- **Backend:** Laravel 12, Filament 5
- **Frontend:** Tailwind CSS
- **Dev Environment:** Laravel Sail or Herd
- **Translations:** Spatie Laravel Translatable (dual language)
- **Media:** Curator Filament
- **Filament Translatable Fields:** Solution Forest: filament-translate-field
- **PDF Generation:** Browsershot (headless Chrome via Puppeteer)

---

## Document Numbering System

**Format:** `{TYPE}/{NUM}/{ROMAN_MONTH}/{YY}/{SUFFIX}`

| Segment | Description |
|---|---|
| TYPE | `QUO` for proposals, `INV` for invoices |
| NUM | Auto-increment integer, resets monthly |
| ROMAN_MONTH | Month in Roman numerals (I–XII) |
| YY | Last two digits of year |
| SUFFIX | Default `NEW`, admin can override (e.g., `REV` for revision) |

**Examples:**
- `QUO/001/IV/26/NEW` — 1st proposal of April 2026
- `INV/003/IV/26/REV` — 3rd invoice of April 2026, revision

**Database fields per document:**
- `document_number` — full formatted string (display/search)
- `document_number_raw` — integer (the auto-increment portion)
- `document_number_suffix` — string, default `NEW`
- `document_number_override` — boolean flag

**Unique constraint:** compound on `type + raw_number + month + year` to prevent duplicates within a month.

---

## Entities

### 1. Company (Issuing Entity)

Represents the brands/companies that issue documents.

| Field | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `company_name` | string | Legal entity name |
| `brand_name` | string | Trading/brand name |
| `logo` | media (Curator) | |
| `address` | text | |
| `email_1` | string | |
| `email_2` | string | nullable |
| `phone_1` | string | |
| `phone_2` | string | nullable |
| `tax_id` | string | NPWP |
| `website` | string | nullable |
| `default_currency` | string | default `IDR` |
| `color_primary` | string | hex, for branded PDF |
| `color_secondary` | string | hex, for branded PDF |
| `footer_text` | text | translatable, appears on every document |
| `bank` | JSON array | non-translatable |
| `pic` | JSON array | non-translatable |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**`bank` structure:**
```json
[
  {
    "bank_name": "BCA",
    "account_name": "PT Digital Citra Kreatif",
    "account_number": "1234567890"
  }
]
```

**`pic` structure:**
```json
[
  {
    "pic_name": "Henry",
    "pic_role": "Director",
    "pic_sign": "media_id or URL"
  }
]
```

---

### 2. Proposal

| Field | Type | Translatable | Notes |
|---|---|---|---|
| `id` | bigint | — | PK |
| `document_number` | string | no | Full formatted number |
| `document_number_raw` | integer | no | Auto-increment portion |
| `document_number_suffix` | string | no | Default `NEW` |
| `document_number_override` | boolean | no | |
| `client_company` | string | no | Client's company name |
| `client_name` | string | no | Client contact person |
| `client_email` | string | no | |
| `issue_date` | date | no | Default from `created_at`, can override |
| `valid_until` | date | no | Default: issue_date + 30 days. Set to `null` for infinite validity |
| `currency` | string | no | Default `IDR` |
| `brief` | JSON array | **yes** | |
| `portfolios` | JSON array | no | See structure below |
| `core_services` | JSON array | **yes** | |
| `features` | JSON array | **yes** | |
| `server` | JSON array | **yes** | |
| `assets` | JSON array | **yes** | |
| `security` | JSON array | **yes** | |
| `support` | JSON array | **yes** | |
| `offer_name_1` | string | no | |
| `offer_1_price` | decimal | no | Final price after discount |
| `offer_1_original_price` | decimal | no | nullable, only if discounted |
| `offer_1_renewal_price` | decimal | no | |
| `offer_1_project_timeline` | JSON array | **yes** | See structure below |
| `offer_name_2` | string | no | nullable |
| `offer_2_price` | decimal | no | nullable |
| `offer_2_original_price` | decimal | no | nullable |
| `offer_2_renewal_price` | decimal | no | nullable |
| `offer_2_project_timeline` | JSON array | **yes** | See structure below |
| `additional_benefit` | JSON array | **yes** | |
| `add_on` | JSON array | **yes** | See structure below |
| `payment` | JSON array | **yes** | See structure below |
| `terms_condition` | JSON array | **yes** | See structure below |
| `tax_rate` | decimal | no | percentage, default 11 (PPN) |
| `tax_amount` | decimal | no | computed |
| `total_amount` | decimal | no | computed |
| `notes` | text | no | Internal, not shown on frontend |
| `status` | enum | no | `draft`, `published` |
| `access_username` | string | no | nullable, per-record override |
| `access_password` | string | no | nullable, hashed, per-record override |
| `user_id` | FK | no | Author/admin who created |
| `company_id` | FK | no | Issuing company |
| `deleted_at` | timestamp | — | Soft delete |
| `created_at` | timestamp | — | |
| `updated_at` | timestamp | — | |

**`portfolios` structure:**
```json
[
  {
    "portfolio_name": "Happy Dental Clinic",
    "portfolio_image_url": "https://...",
    "portfolio_link": "https://..."
  }
]
```

**`offer_X_project_timeline` structure (translatable per sub-field):**
```json
[
  {
    "activity_name": "Discovery & Research",
    "activity_pic": "Henry",
    "activity_days": 5
  }
]
```

**`add_on` structure:**
```json
[
  {
    "name": "SEO Setup",
    "description": "On-page SEO optimization...",
    "price": 2500000
  }
]
```

**`payment` structure:**
```json
[
  {
    "info": "Payment via bank transfer",
    "down_payment_amount": 5000000
  }
]
```

**`terms_condition` structure:**
```json
[
  {
    "title": "Project Scope",
    "description": "Any changes beyond the agreed scope..."
  }
]
```

---

### 3. Invoice

| Field | Type | Translatable | Notes |
|---|---|---|---|
| `id` | bigint | — | PK |
| `document_number` | string | no | Full formatted number |
| `document_number_raw` | integer | no | |
| `document_number_suffix` | string | no | Default `NEW` |
| `document_number_override` | boolean | no | |
| `client_company` | string | no | Frozen at creation |
| `client_name` | string | no | Frozen at creation |
| `client_email` | string | no | Frozen at creation |
| `issue_date` | date | no | Default from `created_at`, can override |
| `due_date` | date | no | Default: issue_date + 30 days |
| `currency` | string | no | Default `IDR` |
| `items` | JSON array | **yes** | See structure below |
| `tax_rate` | decimal | no | percentage |
| `tax_amount` | decimal | no | computed |
| `subtotal` | decimal | no | computed |
| `total` | decimal | no | computed |
| `notes` | text | no | Internal |
| `status` | enum | no | `draft`, `published` |
| `payment_status` | enum | no | `unpaid`, `partially_paid`, `paid`, `overdue`, `cancelled` |
| `paid_amount` | decimal | no | Default 0 |
| `paid_at` | timestamp | no | nullable |
| `payment_method` | string | no | nullable |
| `access_username` | string | no | nullable, per-record override |
| `access_password` | string | no | nullable, hashed, per-record override |
| `proposal_id` | FK | no | nullable, link back to source proposal |
| `user_id` | FK | no | |
| `company_id` | FK | no | |
| `deleted_at` | timestamp | — | Soft delete |
| `created_at` | timestamp | — | |
| `updated_at` | timestamp | — | |

**`items` structure:**
```json
[
  {
    "title": "Website Development",
    "description": "Full custom website build...",
    "price": 15000000
  }
]
```

---

### 4. User (Admin/Author)

Uses Filament's built-in user authentication. Standard fields: `name`, `email`, `password`, plus any Filament defaults.

---

## Client Access (Frontend Authentication)

Simple document-level authentication. No user accounts, no registration, no tokens.

**Per-document fields** (on both Proposal and Invoice):
- `access_username` — nullable string
- `access_password` — nullable string, stored hashed

**Access flow:**
1. Client visits the document URL (e.g., `/proposal/{slug}` or `/invoice/{slug}`)
2. If the document has `access_username` and `access_password` set → prompt for credentials
3. If per-document credentials are empty → fall back to global credentials from `.env` (`GLOBAL_ACCESS_USERNAME`, `GLOBAL_ACCESS_PASSWORD`)
4. Session-based: once authenticated for a document, client stays authenticated for that browser session

---

## Proposal → Invoice Conversion

One-click action in Filament admin. No selection modal — always converts using Offer 1.

**Copied fields (frozen snapshot, not linked):**
- `client_company`, `client_name`, `client_email`
- `company_id`, `user_id`
- `currency`
- `tax_rate`
- `access_username`, `access_password` (if set)

**Generated fields:**
- `document_number` — auto-generated as `INV/...`
- `issue_date` — today
- `due_date` — today + 30 days

**Items generation from Offer 1:**
- `offer_name_1` → first invoice item title
- `offer_1_price` → first invoice item price
- Description can be auto-generated or left empty for admin to fill

**Linked:**
- `proposal_id` — references the source proposal

**Defaults:**
- `status` → `draft`
- `payment_status` → `unpaid`

---

## Renewal Invoices

Proposals contain `offer_1_renewal_price`, which represents the recurring annual/periodic cost after the initial project. Admin can generate renewal invoices directly from a proposal.

**Action:** "Create Renewal Invoice" on Proposal (separate from "Convert to Invoice")

**Behavior:**
- Same as standard conversion, except uses `offer_1_renewal_price` instead of `offer_1_price`
- Item title auto-generated as "[offer_name_1] — Renewal" (admin can edit)
- `proposal_id` links back to the same source proposal
- A single proposal can have multiple invoices (initial + multiple renewals over the years)

**Proposal → Invoice relationship:** one-to-many. The proposal table in Filament shows a badge or count of linked invoices, and each invoice links back to its source proposal.

---

## Scheduled Artisan Command

**Command:** `php artisan documents:check-overdue`

**Recommended schedule:** Daily via Laravel's scheduler (`schedule:run` in cron)

**Two operations per run:**

**1. Expire overdue proposals:**
- Condition: `status = 'published'` AND `valid_until IS NOT NULL` AND `valid_until < today`
- Action: Set `status` → `draft` (unpublishes, client can no longer view)

**2. Flag overdue invoices:**
- Condition: `status = 'published'` AND `payment_status IN ('unpaid', 'partially_paid')` AND `due_date < today`
- Action: Set `payment_status` → `overdue` (invoice stays published and visible to client)

**Notes:**
- Command is idempotent — safe to run multiple times
- Already-overdue records are skipped (no repeated writes)
- Proposals with `valid_until = null` (infinite validity) are never auto-expired

---

## PDF Generation

**Engine:** Browsershot (headless Chrome via Puppeteer)

**Flow:**
1. Admin or client clicks "Download PDF" on a proposal or invoice
2. System renders the HTML view (same as frontend display) with print-optimized CSS
3. Browsershot captures and returns PDF
4. PDF uses company branding (logo, colors from `color_primary`/`color_secondary`)

**Requirements:**
- Node.js on server (Laravel Sail handles this)
- Print stylesheet with proper page breaks, margins, headers/footers
- Company letterhead rendered in PDF header

---

## Translation Strategy (Spatie)

**Approach:** Each translatable sub-field within JSON arrays is individually translatable, not the entire array.

**Example — `features` field:**
```json
[
  {
    "feature_name": {
      "en": "Responsive Design",
      "id": "Desain Responsif"
    },
    "feature_description": {
      "en": "Adapts to all screen sizes",
      "id": "Menyesuaikan ke semua ukuran layar"
    }
  }
]
```

This allows the array structure (order, count) to remain consistent across locales while individual text content is translated.

**Non-translatable arrays** (like `portfolios`, `bank`) store plain values without locale keys.

---

## Filament Admin Features

### Resources

- **ProposalResource** — full CRUD with all fields, repeater components for JSON arrays, translation tabs
- **InvoiceResource** — full CRUD, payment status management
- **CompanyResource** — full CRUD, media upload for logo and PIC signatures
- **UserResource** — standard Filament user management

### Filament Table: Proposals

**Columns:** `document_number`, `client_company`, `client_name`, `offer_1_price`, `status`, `issue_date`, `invoices_count` (badge showing number of linked invoices)

**Filters:**
- `status` — select filter: draft, published
- `company_id` — select filter: issuing company
- `has_invoice` — ternary filter: proposals that have/haven't been converted to invoice (via `invoices` relationship existence check)
- `issue_date` — date range picker
- `created_at` — date range picker
- `valid_until` — date range picker (useful for finding expired proposals)

**Searchable columns:** `client_name`, `client_company`, `document_number`

### Filament Table: Invoices

**Columns:** `document_number`, `client_company`, `client_name`, `total`, `payment_status`, `status`, `issue_date`, `due_date`, `proposal` (link to source proposal, if linked)

**Filters:**
- `status` — select filter: draft, published
- `payment_status` — select filter: unpaid, partially_paid, paid, overdue, cancelled
- `company_id` — select filter: issuing company
- `has_proposal` — ternary filter: invoices linked/not linked to a proposal
- `issue_date` — date range picker
- `due_date` — date range picker
- `created_at` — date range picker

**Searchable columns:** `client_name`, `client_company`, `document_number`

### Custom Actions

**Proposal actions:**
- **Convert to Invoice** — one-click, auto-generates invoice from Offer 1 main price, redirects to new invoice edit page
- **Create Renewal Invoice** — one-click, auto-generates invoice from Offer 1 renewal price, redirects to new invoice edit page
- **Duplicate Proposal** — clones all fields into a new draft with fresh auto-generated document number
- **Generate PDF** — download PDF via Browsershot

**Invoice actions:**
- **Duplicate Invoice** — clones all fields into a new draft with fresh auto-generated document number
- **Generate PDF** — download PDF via Browsershot
- **Mark as Paid** — quick action modal with `paid_amount`, `payment_method`, `paid_at` fields
- **View Proposal** — link to source proposal (visible only if `proposal_id` is set)

### Dashboard Widgets

- **Total Outstanding** — sum of `total` for all unpaid/partially_paid published invoices
- **Overdue Invoices** — count of published invoices where `payment_status = 'overdue'`
- **Pending Proposals** — count of published proposals not yet converted to invoice
- **Revenue This Month** — sum of `paid_amount` for invoices marked paid in current month

---

## Database Migrations Summary

```
companies
proposals
invoices
users (Filament default)
```

---

## Resolved Decisions

1. **Recurring invoices:** ✅ Yes — "Create Renewal Invoice" action on proposals using `offer_1_renewal_price`, linked via `proposal_id`
2. **Client portal:** ✅ No — clients view individual documents only
3. **Digital signature:** ✅ No — not needed
4. **Overdue auto-detection:** ✅ Scheduled artisan command — expires proposals (set to draft) and flags invoices (set payment_status to overdue)
