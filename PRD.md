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

### 2. Client

| Field | Type | Translatable | Notes |
|---|---|---|---|
| `id` | bigint | — | PK |
| `name` | string | no | Client contact person |
| `company` | string | no | Client's company name |
| `email` | string | no | |
| `phone` | string | no | |
| `notes` | JSON array | no | non-translatable |
| `deleted_at` | timestamp | — | Soft delete |
| `created_at` | timestamp | — | |
| `updated_at` | timestamp | — | |

**Relationships:**
- `hasMany(Proposal)`
- `hasMany(Invoice)`
- `hasMany(Service)`

---

### 3. Service

| Field | Type | Translatable | Notes |
|---|---|---|---|
| `id` | bigint | — | PK |
| `name` | string | no | |
| `domain` | string | no | website url |
| `start_date` | string | no | the time when the service is active |
| `renewal_date` | string | no | date and month for renewal |
| `status` | enum | no | `terminated`, `on-going`, `suspended` |
| `notes` | JSON array | no | non-translatable |
| `client_id` | FK | no | has one client |
| `deleted_at` | timestamp | — | Soft delete |
| `created_at` | timestamp | — | |
| `updated_at` | timestamp | — | |

**Relationships:**
- `belongsTo(Client)`
- `hasMany(Proposal)`
- `hasMany(Invoice)`

---

### 4. Proposal

| Field | Type | Translatable | Notes |
|---|---|---|---|
| `id` | bigint | — | PK |
| `document_number` | string | no | Full formatted number |
| `document_number_raw` | integer | no | Auto-increment portion |
| `document_number_suffix` | string | no | Default `NEW` |
| `document_number_override` | boolean | no | |
| `client_id` | FK | no | nullable, optional reference to client record |
| `client_company` | string | no | Frozen snapshot at document creation |
| `client_name` | string | no | Frozen snapshot at document creation |
| `client_email` | string | no | Frozen snapshot at document creation |
| `client_phone` | string | no | Frozen snapshot at document creation |
| `issue_date` | date | no | Default from `created_at`, can override |
| `valid_until` | date | no | Default: issue_date + 30 days. Set to `null` for infinite validity |
| `currency` | string | no | Default `IDR` |
| `brief` | rich text (HTML) | **yes** | TipTap editor |
| `portfolios` | JSON array | no | See structure below |
| `core_services` | rich text (HTML) | **yes** | TipTap editor |
| `features` | rich text (HTML) | **yes** | TipTap editor |
| `server` | rich text (HTML) | **yes** | TipTap editor |
| `assets` | rich text (HTML) | **yes** | TipTap editor |
| `security` | rich text (HTML) | **yes** | TipTap editor |
| `support` | rich text (HTML) | **yes** | TipTap editor |
| `additional_benefit` | rich text (HTML) | **yes** | TipTap editor |
| `payment` | rich text (HTML) | **yes** | TipTap editor |
| `terms_condition` | rich text (HTML) | **yes** | TipTap editor |
| `additional_info` | rich text (HTML) | **yes** | TipTap editor |
| `extra_content_brief` | rich text (HTML) | **yes** | TipTap editor |
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
| `add_on` | JSON array | **yes** | See structure below |
| `tax_rate` | decimal | no | percentage, default 11 (PPN) |
| `tax_amount` | decimal | no | computed |
| `total_amount` | decimal | no | computed |
| `notes` | JSON array | no | Internal, not shown on frontend |
| `status` | enum | no | `draft`, `published` |
| `access_username` | string | no | nullable, per-record override |
| `access_password` | string | no | nullable, hashed, per-record override |
| `user_id` | FK | no | Author/admin who created |
| `company_id` | FK | no | Issuing company |
| `service_id` | FK | no | nullable, related service |
| `deleted_at` | timestamp | — | Soft delete |
| `created_at` | timestamp | — | |
| `updated_at` | timestamp | — | |

---

### 5. Invoice

| Field | Type | Translatable | Notes |
|---|---|---|---|
| `id` | bigint | — | PK |
| `document_number` | string | no | Full formatted number |
| `document_number_raw` | integer | no | |
| `document_number_suffix` | string | no | Default `NEW` |
| `document_number_override` | boolean | no | |
| `client_id` | FK | no | nullable, optional reference to client record |
| `client_company` | string | no | Frozen snapshot at document creation |
| `client_name` | string | no | Frozen snapshot at document creation |
| `client_email` | string | no | Frozen snapshot at document creation |
| `client_phone` | string | no | Frozen snapshot at document creation |
| `issue_date` | date | no | Default from `created_at`, can override |
| `due_date` | date | no | Default: issue_date + 30 days |
| `currency` | string | no | Default `IDR` |
| `items` | JSON array | **yes** | See structure below |
| `tax_rate` | decimal | no | percentage |
| `tax_amount` | decimal | no | computed |
| `subtotal` | decimal | no | computed |
| `total` | decimal | no | computed |
| `notes` | JSON array | no | Internal |
| `status` | enum | no | `draft`, `published` |
| `payment_status` | enum | no | `unpaid`, `partially_paid`, `paid`, `overdue`, `cancelled` |
| `paid_amount` | decimal | no | Default 0 |
| `paid_at` | timestamp | no | nullable |
| `payment_method` | string | no | nullable |
| `access_username` | string | no | nullable, per-record override |
| `access_password` | string | no | nullable, hashed, per-record override |
| `proposal_id` | FK | no | nullable, link back to source proposal |
| `service_id` | FK | no | nullable, related service |
| `user_id` | FK | no | |
| `company_id` | FK | no | |
| `deleted_at` | timestamp | — | Soft delete |
| `created_at` | timestamp | — | |
| `updated_at` | timestamp | — | |

---

### 6. User (Admin/Author)

Uses Filament's built-in user authentication. Standard fields: `name`, `email`, `password`, plus any Filament defaults.

---

## Client Access (Frontend Authentication)

Simple document-level authentication. No user accounts, no registration, no tokens.

**Per-document fields** (on both Proposal and Invoice):
- `access_username` — nullable string
- `access_password` — nullable string, stored hashed

---

## Proposal → Invoice Conversion

One-click action in Filament admin. No selection modal — always converts using Offer 1.

**Copied fields (frozen snapshot, not linked):**
- `client_company`, `client_name`, `client_email`, `client_phone`
- `client_id` (optional reference only, not source of displayed client values)
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

**Linked:**
- `proposal_id` — references the source proposal

**Defaults:**
- `status` → `draft`
- `payment_status` → `unpaid`

**Relationship rules:**
- Proposal has many invoices
- Proposal and invoice store client snapshot fields and remain unchanged even if `clients` data is edited later
- Proposal and invoice may keep optional `client_id` for quick navigation/reference
- If proposal/invoice is created from a selected client, copy `name/company/email/phone` into snapshot fields at creation time
- Service belongs to one client, and proposal/invoice can optionally link to one service (`service_id`)

---

## Renewal Invoices

Proposals contain `offer_1_renewal_price`, which represents the recurring annual/periodic cost after the initial project.

**Action:** "Create Renewal Invoice" on Proposal (separate from "Convert to Invoice")

**Behavior:**
- Same as standard conversion, except uses `offer_1_renewal_price` instead of `offer_1_price`
- Item title auto-generated as `"{offer_name_1} — Renewal"` (admin can edit)
- `proposal_id` links back to the same source proposal
- A single proposal can have multiple invoices (initial + multiple renewals over the years)

---

## Filament Admin Features

### Resources

- **ProposalResource** — full CRUD with all fields, repeater components for JSON arrays, translation tabs
- **InvoiceResource** — full CRUD, payment status management
- **CompanyResource** — full CRUD, media upload for logo and PIC signatures
- **ClientResource** — full CRUD for clients
- **ServiceResource** — full CRUD for services
- **UserResource** — standard Filament user management

### Filament Table: Proposals

**Columns:** `document_number`, `client_company`, `client_name`, `offer_1_price`, `status`, `issue_date`, `invoices_count`

**Searchable columns:** `client_name`, `client_company`, `document_number`

### Filament Table: Invoices

**Columns:** `document_number`, `client_company`, `client_name`, `total`, `payment_status`, `status`, `issue_date`, `due_date`, `proposal`

**Searchable columns:** `client_name`, `client_company`, `document_number`

### Custom Actions

**Proposal actions:**
- **Convert to Invoice**
- **Create Renewal Invoice**
- **Duplicate Proposal**
- **Create Client** — one-click from proposal snapshot fields (`client_name`, `client_company`, `client_email`, `client_phone`) and auto-link `client_id`
- **Create Service** — one-click from proposal and auto-link selected `client_id`
- **Generate PDF**

**Invoice actions:**
- **Duplicate Invoice**
- **Generate PDF**
- **Mark as Paid**
- **Create Client** — one-click from invoice snapshot fields (`client_name`, `client_company`, `client_email`, `client_phone`) and auto-link `client_id`
- **Create Service** — one-click from invoice and auto-link selected `client_id`
- **View Proposal**

---

## Translation Strategy (Spatie)

**Approach:** Each translatable sub-field within JSON arrays is individually translatable, not the entire array.

**Non-translatable arrays** (like `portfolios`, `bank`, `client.notes`, `service.notes`, `proposal.notes`, `invoice.notes`) store plain values without locale keys.

---

## PDF Generation

**Engine:** Browsershot (headless Chrome via Puppeteer)

**Flow:**
1. Admin or client clicks "Download PDF" on a proposal or invoice.
2. System renders the HTML view with print-optimized CSS.
3. Browsershot captures and returns PDF.
4. PDF uses company branding (logo, colors from `color_primary`/`color_secondary`).

---

## Scheduled Artisan Command

**Command:** `php artisan documents:check-overdue`

**Recommended schedule:** Daily via Laravel scheduler.

**Operations:**
1. Expire proposals where `status = published`, `valid_until IS NOT NULL`, and `valid_until < today` → set `status = draft`.
2. Flag invoices where `status = published`, `payment_status IN (unpaid, partially_paid)`, and `due_date < today` → set `payment_status = overdue`.

---

## Database Migrations Summary

```
companies
clients
services
proposals
invoices
users (Filament default)
```

---

## Resolved Decisions

1. **Recurring invoices:** ✅ Yes — use "Create Renewal Invoice" action on proposals.
2. **Client portal:** ✅ No — clients view individual documents only.
3. **Digital signature:** ✅ No — not needed.
4. **Overdue auto-detection:** ✅ Scheduled artisan command.
5. **Client snapshot policy:** ✅ Proposal and Invoice keep frozen `client_*` fields; editing Client later does not mutate existing documents.
6. **Service management:** ✅ Service is a first-class entity and can be created from Proposal/Invoice in one click.
