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
| `address` | text | no | nullable, may contain line breaks or `<br>` |
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
| `price` | decimal | no | service price |
| `currency` | string | no | default `IDR` |
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
| `client_address` | text | no | nullable, frozen snapshot at document creation; may contain line breaks or `<br>` |
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
| `client_address` | text | no | nullable, frozen snapshot at document creation; may contain line breaks or `<br>` |
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
| `paid_at` | timestamp | no | nullable — auto-set to current datetime when `payment_status` changes to `paid` |
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

### 7. Portfolio

Reusable portfolio items shown inside proposals.

| Field | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `name` | string | Project name |
| `image_url` | string | nullable — Curator media ID (uploaded via Curator plugin) |
| `image_url_external` | string | nullable — Direct external image URL (e.g. CDN, remote link) |
| `url_link` | string | nullable — Link to the live project or case study |
| `deleted_at` | timestamp | Soft delete |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Image source rules:**
- Only one of `image_url` or `image_url_external` should be populated per record.
- The Filament form uses a toggle to switch between Curator upload mode and external URL mode.
- The table resolves the display URL as: `image->url` (Curator) → fallback `image_url_external`.

**Relationships:**
- `belongsToMany(Proposal)` via `portfolio_proposal` pivot table

---

### 8. SPK (Surat Perjanjian Kerja)

Represents a work agreement that can be created manually or generated from a proposal. SPKs are shared with clients before manual signing and must be printable/PDF-friendly.

| Field | Type | Translatable | Notes |
|---|---|---|---|
| `id` | bigint | — | PK |
| `document_number` | string | no | Full formatted number using `SPK/{NUM}/{ROMAN_MONTH}/{YY}/{SUFFIX}` |
| `document_number_raw` | integer | no | Auto-increment portion |
| `document_number_suffix` | string | no | Default `NEW` |
| `document_number_override` | boolean | no | |
| `slug` | string | no | Public document slug |
| `spk_date` | date | no | Agreement date, defaults to today |
| `client_company` | string | no | Frozen snapshot |
| `client_pic_name` | string | no | Frozen snapshot |
| `client_pic_role` | string | no | nullable, frozen snapshot |
| `client_address` | text | no | nullable, frozen snapshot; may contain line breaks or `<br>` |
| `company_name` | string | no | Frozen snapshot of issuing company legal name |
| `company_pic_name` | string | no | Frozen snapshot, selectable from company `pic` when generated |
| `company_pic_role` | string | no | nullable, frozen snapshot, selectable from company `pic` when generated |
| `company_address` | text | no | nullable, frozen snapshot |
| `title` | rich text/string | **yes** | Agreement heading/title |
| `subject` | string | **yes** | Agreement subject, e.g. `JASA PEMBUATAN WEBSITE` |
| `content` | rich text (HTML) | **yes** | Main SPK content |
| `status` | enum | no | `draft`, `published`; generated SPKs default to `published` |
| `access_username` | string | no | nullable, per-record override |
| `access_password` | string | no | nullable, hashed, per-record override |
| `access_credentials_updated_at` | timestamp | no | nullable |
| `notes` | JSON array | no | Internal |
| `proposal_id` | FK | no | nullable, source proposal reference |
| `client_id` | FK | no | nullable, optional reference only |
| `company_id` | FK | no | nullable, optional reference only |
| `user_id` | FK | no | Author/admin who created |
| `updated_by` | FK | no | nullable |
| `deleted_at` | timestamp | — | Soft delete |
| `created_at` | timestamp | — | |
| `updated_at` | timestamp | — | |

**Relationships:**
- `belongsTo(Proposal)` nullable
- `belongsTo(Client)` nullable
- `belongsTo(Company)` nullable
- `belongsTo(User)`

**Snapshot rules:**
- SPK client/company fields are frozen snapshots and remain editable per SPK.
- Existing SPKs do not auto-sync when proposal, client, or company records change.
- `client_id`, `company_id`, and `proposal_id` are references for navigation/convenience only.

**Proposal content defaults:**
- Multiple named packs are allowed. Exactly one pack is marked `is_default`.
- New proposals (Filament and API `mode: default`) use the default pack unless another pack is selected.
- On proposal create/edit, admin can pick a pack and Load it; that **replaces all** proposal content fields from the pack.

**Default content:**
- Admin can manage default SPK title, subject, and content from the dashboard.
- New SPKs copy default content into the SPK record; copied content can be edited per SPK.
- Default content supports placeholders that are resolved once when an SPK is created:
  - `{{ spk_number }}`
  - `{{ spk_date }}`
  - `{{ client_company }}`
  - `{{ client_pic_name }}`
  - `{{ client_pic_role }}`
  - `{{ client_address }}`
  - `{{ company_name }}`
  - `{{ company_pic_name }}`
  - `{{ company_pic_role }}`
  - `{{ company_address }}`
  - `{{ proposal_number }}`
  - `{{ proposal_date }}`
  - `{{ offer_name }}`
  - `{{ offer_price }}`

**Proposal → SPK generation:**
- One proposal can generate many SPKs.
- Proposal action opens a small modal to choose a company PIC from `company.pic`.
- Selected company PIC is copied into editable SPK fields.
- Generated SPKs copy:
  - `client_company` → `client_company`
  - `client_name` → `client_pic_name`
  - `client_address` → `client_address`
  - `company.company_name` → `company_name`
  - `company.address` → `company_address`
  - selected company PIC name/role → `company_pic_name`, `company_pic_role`
  - `proposal_id`, `client_id`, `company_id`, `user_id`
  - proposal access credentials, if set
- Generated SPKs default to `published`.

**Client access and PDF:**
- Public SPK pages use the same document access credential model as proposals/invoices, with per-record credentials and `.env` global fallback.
- Draft SPKs return 404 to public clients.
- Public SPK pages include a Download PDF button.
- SPK view and PDF output are print-first, A4-friendly, and include manual signature areas for both parties.

---

## Client Access (Frontend Authentication)

Simple document-level authentication. No user accounts, no registration, no tokens.

**Per-document fields** (on both Proposal and Invoice):
- `access_username` — nullable string
- `access_password` — nullable string, stored hashed

SPK uses the same access credential fields and fallback behavior.

---

## Proposal → Invoice Conversion

One-click action in Filament admin. No selection modal — always converts using Offer 1.

**Copied fields (frozen snapshot, not linked):**
- `client_company`, `client_name`, `client_address`, `client_email`, `client_phone`
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
- If proposal/invoice is created from a selected client, copy `name/company/address/email/phone` into snapshot fields at creation time
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
- **Create SPK** — create a published SPK from proposal snapshot fields and selected company PIC
- **Duplicate Proposal**
- **Create Client** — one-click from proposal snapshot fields (`client_name`, `client_company`, `client_address`, `client_email`, `client_phone`) and auto-link `client_id`
- **Create Service** — one-click from proposal and auto-link selected `client_id`
- **Generate PDF**

**SPK actions:**
- **Duplicate SPK**
- **View Proposal**
- **Generate PDF**

**Invoice actions:**
- **Duplicate Invoice**
- **Generate PDF**
- **Mark as Paid**
- **Create Client** — one-click from invoice snapshot fields (`client_name`, `client_company`, `client_address`, `client_email`, `client_phone`) and auto-link `client_id`
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

## Remote Document API

Machine API so an integrating app or AI agent can create published documents without a Filament login. This is authenticated machine access, not a public unauthenticated endpoint.

**Auth:** shared API key from `.env` (`DOCUMENT_API_KEY`). Send `Authorization: Bearer {key}` or `X-Api-Key`. Compare with `hash_equals`. Empty key means the API is disabled (401).

**Author:** `DOCUMENT_API_USER_ID` points at an existing Kanjo user. Every API-created document uses that `user_id`. Do not accept `user_id` in the payload.

**Status:** always `published`. Do not accept `status` in the payload.

**Access credentials:** do not set per-document `access_username` / `access_password`. Public pages use `GLOBAL_ACCESS_*`.

**Rate limit:** 60 requests per minute.

**Prefix:** `/api/v1`

### Endpoints

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/v1` | Index (links to guide, OpenAPI, catalogs) |
| GET | `/api/v1/guide` | Agent guide (markdown) |
| GET | `/api/v1/openapi.json` | OpenAPI document |
| GET | `/api/v1/companies` | Issuing companies + PIC list |
| GET | `/api/v1/companies/{id}` | One company + PIC list |
| GET | `/api/v1/clients` | Search clients (`?q=` matches name, company, email) |
| GET | `/api/v1/clients/{id}` | Client plus related proposals, invoices, services, SPKs |
| GET | `/api/v1/proposals` | Search proposals (`?q=`, `client_id`, `company_id`, `status`) |
| GET | `/api/v1/proposals/{id}` | Proposal summary plus related invoices, SPKs, service ids |
| GET | `/api/v1/invoices` | Search invoices (`?q=`, `client_id`, `service_id`, `proposal_id`, `status`, `payment_status`) |
| GET | `/api/v1/invoices/{id}` | Invoice summary |
| GET | `/api/v1/spks` | Search SPKs (`?q=`, `client_id`, `proposal_id`, `company_id`, `status`) |
| GET | `/api/v1/spks/{id}` | SPK summary |
| GET | `/api/v1/services` | Search services (`?q=` name/domain, `client_id`, `status`) |
| GET | `/api/v1/services/{id}` | Service plus related invoices and proposal ids |
| GET | `/api/v1/content-defaults/proposal` | Current proposal content defaults |
| GET | `/api/v1/content-defaults/spk` | Current SPK content defaults + placeholders |
| GET | `/api/v1/proposals/skeleton` | Required proposal payload, all content `default` |
| GET | `/api/v1/invoices/skeleton` | Required invoice payload |
| GET | `/api/v1/spks/skeleton` | Required SPK payload |
| POST | `/api/v1/proposals` | Create proposal (`dry_run` optional) |
| POST | `/api/v1/invoices` | Create standalone invoice |
| POST | `/api/v1/spks` | Create standalone SPK |
| POST | `/api/v1/proposals/{id}/invoices` | Invoice from proposal (Offer 1 by default) |
| POST | `/api/v1/proposals/{id}/spks` | SPK from proposal |
| PATCH | `/api/v1/companies/{id}` | Partial update (no logo) |
| PATCH | `/api/v1/clients/{id}` | Partial update; does not mutate existing documents |
| PATCH | `/api/v1/services/{id}` | Partial update (`renewal_date`, status, etc.) |
| PATCH | `/api/v1/proposals/{id}` | Partial update, any author |
| PATCH | `/api/v1/invoices/{id}` | Partial update, any author |
| PATCH | `/api/v1/spks/{id}` | Partial update, any author |

List/show for clients, proposals, invoices, SPKs, and services is included so agents can look up an existing client company and related documents. List payloads are summaries only (no rich-text body, no passwords). Default limit 50, max 100 (`?limit=`). `q` on documents matches `document_number` plus frozen client name/company.

**Update:** `PATCH` any existing company, client, service, proposal, invoice, or SPK (not limited to records created by the API user). Send only fields to change. Changing a Client master record does **not** rewrite frozen `client_*` snapshot fields on documents. Changing `client_id` on a document does not copy snapshot fields unless the payload also sends those snapshot fields. Document `content` keys are optional on PATCH; each sent key still uses `default` / `override` / `empty`. Delete remains Filament-only (API `DELETE` is 405).

**Lookup example:** `GET /api/v1/clients?q=Rovela` then `GET /api/v1/clients/{id}` returns that client’s proposals, invoices, services, and SPKs. `GET /api/v1/proposals?q=Rovela` also matches frozen `client_company` on documents with no `client_id`. Services belong to a client; invoices optionally link `service_id` (proposals link to services only through invoices).

### Content modes

Each stored content field on a create payload must declare a mode. Omitted fields are a 422.

```json
{ "mode": "default" }
{ "mode": "empty" }
{ "mode": "override", "value": { "en": "...", "id": "..." } }
```

| Mode | Meaning |
|---|---|
| `default` | Copy from Proposal / SPK Content Defaults into the document and freeze. Optional `template` looks up a different defaults key (timeline templates, `ecommerce_features`, `marketing_program`). |
| `override` | Use `value`. Do not mix in defaults for that field. Missing locale is stored empty. A non-locale string is copied to both locales. |
| `empty` | Store empty / `[]`. Do not copy defaults. |

Rich-text override values may be Markdown or HTML. If the string contains an HTML tag, treat as HTML; otherwise convert Markdown to HTML. Repeaters stay JSON.

Mode applies to both `en` and `id`.

**Proposal `content` keys (all required):** `brief`, `extra_content_brief`, `core_services`, `features`, `server`, `assets`, `security`, `support`, `additional_benefit`, `add_on`, `payment`, `terms_condition`, `additional_info`, `faq`, `our_process`, `about_us`, `video_testimonials`, `client_logos`, `offer_1_project_timeline`, `offer_2_project_timeline`

`video_testimonials` and `client_logos` are shared (not translatable). Override value is a single array.

When a timeline field is `default`, optional `template` or payload `timeline_template` selects `short_project_timeline`, `business_project_timeline`, `prime_project_timeline`, `corporate_project_timeline`, or `custom_project_timeline`.

**SPK `content` keys (all required):** `title`, `subject`, `content`

SPK `default` for `subject`/`content` copies SPK Content Defaults and resolves placeholders once. `title` has no default; `default` stores empty.

**Invoice:** no content-default catalog. Standalone create requires `items`. `content.additional_info` mode may be `override` or `empty` only (`default` is invalid). From-proposal create may use `additional_info` mode `default` to copy the proposal’s additional info. Nested create copies Offer 1 into items unless `items` is sent; optional `offer` `1|2` and `renewal` boolean.

### Client rule

1. If `client_id` is sent, load that client and copy into frozen snapshot fields. Optional `client` object overrides snapshot fields only (does not update the Client record).
2. If `client_id` is omitted, create a new Client from snapshot fields and set `client_id`. Do not match existing clients by email.
3. After create, editing the Client never changes the document.

Required snapshot when creating a client: `client.company`, `client.name`. Email, phone, address optional. SPK may send `client.pic_role` for `client_pic_role`.

### Other create rules

- `company_id` required on standalone creates.
- `service_id` optional; do not auto-create services.
- `activate_translation` default `false`.
- Currency default: company `default_currency` or `IDR`.
- Proposal `tax_rate` default `11`. Invoice `tax_rate` default `0` unless sent (from-proposal copies the proposal rate).
- `issue_date` / `spk_date` default today. Proposal `valid_until` and invoice `due_date` default +30 days.
- Document number and slug auto-generated. No override in v1.
- Offer 2 and portfolios optional / omitted in v1 unless sent on the proposal payload (`offer_name_2`, prices).
- From-proposal SPK: `company_pic_index` (0-based) or `company_pic_name` + `company_pic_role`. If omitted, use the company’s first PIC.

### Dry-run

POST bodies may include `"dry_run": true`. Validate and preview; do not insert documents or clients. Response includes `valid`, `would_create`, `resolved_content_preview`, and `warnings`. Real create omits `dry_run` or sets `false`.

### Success body

```json
{
  "data": {
    "type": "proposal",
    "id": 123,
    "document_number": "QUO/005/VIII/26/NEW",
    "status": "published",
    "public_url": "https://example.test/proposal/...",
    "pdf_url": "https://example.test/proposal/.../pdf",
    "client_id": 88
  }
}
```

Do not return passwords.

### Agent guide

Canonical agent operating manual: `docs/api/agent-guide.md`, also served at authenticated `GET /api/v1/guide`. Agents should read that document in full, then use live catalogs/skeletons for IDs and defaults. Do not treat `openapi.json` as a complete contract.

### Errors

| Status | Meaning |
|---|---|
| 401 | Missing/invalid API key, or key not configured |
| 404 | Unknown proposal/company/client id |
| 422 | Validation. Includes `errors`, `missing_content_fields`, and `hint` |
| 429 | Rate limited |
| 503 | `DOCUMENT_API_USER_ID` missing or not a real user |

---

## Resolved Decisions

1. **Recurring invoices:** ✅ Yes — use "Create Renewal Invoice" action on proposals.
2. **Client portal:** ✅ No — clients view individual documents only.
3. **Digital signature:** ✅ No — not needed.
4. **Overdue auto-detection:** ✅ Scheduled artisan command.
5. **Client snapshot policy:** ✅ Proposal and Invoice keep frozen `client_*` fields; editing Client later does not mutate existing documents.
6. **Service management:** ✅ Service is a first-class entity and can be created from Proposal/Invoice in one click.
7. **Remote Document API:** ✅ Shared `.env` API key, always published, explicit per-field content modes, auto-create Client, global document credentials, dry-run, discovery catalogs for AI agents.
