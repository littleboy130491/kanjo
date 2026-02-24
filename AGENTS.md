# AGENTS.md

## Project Overview

This is a **quotation and invoice management web app** for a web design agency with multiple brands. Read `PRD.md` in the project root **before doing anything**. The PRD is the single source of truth for all requirements, field definitions, and business logic.

**Do not assume or invent requirements. If something is not in the PRD, ask.**

---

## Critical Rules

1. **Read PRD.md first.** Every task you do must align with the PRD. Re-read the relevant section before starting each phase.
2. **Work in small steps.** One migration, one model, one resource at a time. Never scaffold multiple features in a single step.
3. **Commit after every meaningful change.** A meaningful change is: one migration, one model, one resource, one action, one command, etc. Never bundle unrelated changes.
4. **Test before committing.** Run migrations, check for syntax errors, verify the admin panel loads. Do not commit broken code.
5. **Do not refactor ahead of need.** Build what the PRD specifies. No premature abstractions, no "nice to have" additions.
6. **Ask before deviating.** If you encounter a decision not covered by the PRD, stop and ask rather than guessing.

---

## Tech Stack

| Layer | Technology | Notes |
|---|---|---|
| Framework | Laravel 12 | |
| Admin Panel | Filament 5 | All CRUD, actions, dashboard |
| CSS | Tailwind CSS | Frontend views + PDF styling |
| Dev Environment | Laravel Sail or Herd | Sail preferred for Browsershot (needs Node.js) |
| Translations | Spatie Laravel Translatable | Dual language (EN/ID) |
| Media | Curator (Filament plugin) | For logos, portfolio images, PIC signatures |
| PDF | Browsershot | Headless Chrome via Puppeteer |

---

## Commit Convention

Format: `type(scope): description`

```
feat(migration): create companies table
feat(model): add Company model with translatable fields
feat(resource): add CompanyResource with CRUD
feat(migration): create proposals table
feat(model): add Proposal model with relationships
feat(resource): add ProposalResource table columns and filters
feat(resource): add ProposalResource form with repeaters
feat(action): add convert-to-invoice action on Proposal
feat(action): add duplicate action on Proposal
feat(command): add documents:check-overdue artisan command
fix(model): fix proposal numbering auto-increment logic
refactor(resource): extract shared document number logic
```

Keep commits **atomic**. One logical change per commit. If you find yourself writing "and" in the commit message, split it into two commits.

---

## Development Phases

Execute these phases **in order**. Complete each phase fully before moving to the next. Each phase lists the exact commits expected.

### Phase 1: Project Setup

1. Install Laravel 12 with Sail
2. Install and configure Filament 5
3. Install Spatie Laravel Translatable
4. Install Curator for Filament
5. Install Browsershot
6. Configure `.env` with `GLOBAL_ACCESS_USERNAME` and `GLOBAL_ACCESS_PASSWORD`
7. Configure Filament panel provider (branding, navigation groups)
8. Configure supported locales: `en`, `id`

**Verify:** Admin panel loads at `/admin`, login works.

### Phase 2: Company Entity

1. Create `companies` migration — follow the PRD field list exactly
2. Create `Company` model — define `$casts` for JSON fields (`bank`, `pic`), set up Spatie translatable for `footer_text`
3. Create `CompanyResource` — form with repeaters for `bank` and `pic` arrays, Curator media picker for `logo`
4. Seed at least one test company

**Verify:** Can create, edit, delete companies. Logo uploads work. Bank/PIC repeaters work.

### Phase 3: Proposal Entity

1. Create `proposals` migration — follow the PRD field list exactly. Note: `decimal` fields should use `decimal(15, 2)`. JSON fields should be `json` type. Add foreign keys for `user_id` and `company_id`. Add soft deletes.
2. Create `Proposal` model:
   - Define `$casts` for all JSON fields and enums
   - Set up Spatie translatable for all translatable JSON fields (see PRD for which fields are translatable)
   - Define relationships: `belongsTo(Company)`, `belongsTo(User)`, `hasMany(Invoice)`
   - Add `booted()` method for auto-generating `document_number` on creating
3. Create `ProposalResource` table:
   - Columns as specified in PRD: `document_number`, `client_company`, `client_name`, `offer_1_price`, `status`, `issue_date`, `invoices_count`
   - Filters as specified: `status`, `company_id`, `has_invoice` (ternary), `issue_date`, `created_at`, `valid_until` (all date filters as range pickers)
   - Searchable: `client_name`, `client_company`, `document_number`
   - Default sort: `created_at` desc
4. Create `ProposalResource` form:
   - Section: Client Info (`client_company`, `client_name`, `client_email`)
   - Section: Document Settings (`document_number` fields, `issue_date`, `valid_until`, `currency`, `company_id`, `status`, `access_username`, `access_password`)
   - Section: Brief (repeater, translatable)
   - Section: Portfolios (repeater, non-translatable — `portfolio_name`, `portfolio_image_url`, `portfolio_link`)
   - Sections for each translatable array: Core Services, Features, Server, Assets, Security, Support
   - Section: Offer 1 (name, prices, timeline repeater)
   - Section: Offer 2 (same structure, all nullable)
   - Section: Additional Benefits, Add-ons, Payment, Terms & Conditions (repeaters)
   - Section: Tax & Totals (tax_rate, computed tax_amount, computed total_amount)
   - Section: Internal (`notes`)

**Verify:** Can create a full proposal with all fields. Translatable fields show EN/ID tabs. Repeaters add/remove items. Document number auto-generates.

### Phase 4: Invoice Entity

1. Create `invoices` migration — follow PRD exactly. Foreign keys for `proposal_id` (nullable), `user_id`, `company_id`. Soft deletes.
2. Create `Invoice` model:
   - `$casts`, translatable fields, relationships
   - `belongsTo(Proposal)` (nullable), `belongsTo(Company)`, `belongsTo(User)`
   - Auto-generate `document_number` on creating
3. Create `InvoiceResource` table:
   - Columns as PRD: `document_number`, `client_company`, `client_name`, `total`, `payment_status`, `status`, `issue_date`, `due_date`, `proposal` (link)
   - Filters as PRD: `status`, `payment_status`, `company_id`, `has_proposal` (ternary), `issue_date`, `due_date`, `created_at`
   - Searchable: `client_name`, `client_company`, `document_number`
4. Create `InvoiceResource` form:
   - Simpler than proposal: client info, document settings, items repeater, tax/totals, payment status fields, notes

**Verify:** Can create invoices manually. All filters and search work. Proposal link shows when linked.

### Phase 5: Document Numbering Logic

1. Create a shared service/trait: `DocumentNumberGenerator`
   - Input: document type (`QUO` or `INV`), optional suffix override
   - Logic: query the max `document_number_raw` for this type + current month + current year, increment by 1
   - Format: `{TYPE}/{NUM padded to 3 digits}/{ROMAN_MONTH}/{YY}/{SUFFIX}`
   - Roman month mapping: `1 => 'I', 2 => 'II', ... 12 => 'XII'`
2. Integrate into Proposal and Invoice model `creating` events
3. Add manual override: if `document_number_override` is true, skip auto-generation
4. Add validation: compound unique on `type + raw_number + month + year`

**Verify:** Create multiple proposals in same month — numbers increment. New month resets to 001. Manual override works. Duplicate numbers are rejected.

### Phase 6: Custom Actions

1. **Convert to Invoice** (Proposal action):
   - Creates new Invoice with frozen client info from proposal
   - Copies: `client_company`, `client_name`, `client_email`, `company_id`, `user_id`, `currency`, `tax_rate`, `access_username`, `access_password`
   - Generates items from Offer 1: `offer_name_1` → item title, `offer_1_price` → item price
   - Sets `proposal_id`, `status = draft`, `payment_status = unpaid`
   - Redirects to new invoice edit page
2. **Create Renewal Invoice** (Proposal action):
   - Same as above but uses `offer_1_renewal_price` instead of `offer_1_price`
   - Item title: `"{offer_name_1} — Renewal"`
3. **Duplicate Proposal** (Proposal action):
   - Clones all fields
   - Resets: fresh `document_number` (auto-generated), `status = draft`, `document_number_override = false`
   - Clears: `deleted_at`
4. **Duplicate Invoice** (Invoice action):
   - Same clone logic as proposal duplication
   - Resets: fresh `document_number`, `status = draft`, `payment_status = unpaid`, `paid_amount = 0`, `paid_at = null`, `payment_method = null`
5. **Mark as Paid** (Invoice action):
   - Modal form: `paid_amount`, `payment_method`, `paid_at` (default now)
   - Updates `payment_status` to `paid` (or `partially_paid` if `paid_amount < total`)
6. **View Proposal** (Invoice action):
   - Simple URL action, visible only when `proposal_id` is not null
   - Links to ProposalResource edit page

**Verify:** Each action works end-to-end. Conversion creates correct invoice. Renewal uses renewal price. Duplicate produces valid independent copy. Mark as Paid updates status correctly.

### Phase 7: Dashboard Widgets

1. **Total Outstanding** — `Invoice::where('status', 'published')->whereIn('payment_status', ['unpaid', 'partially_paid', 'overdue'])->sum('total')`
2. **Overdue Invoices** — count where `payment_status = 'overdue'`
3. **Pending Proposals** — published proposals with zero linked invoices
4. **Revenue This Month** — sum of `paid_amount` where `paid_at` is in current month

**Verify:** Widgets show correct numbers. Create test data and confirm calculations.

### Phase 8: Scheduled Command

1. Create `app/Console/Commands/CheckOverdueDocuments.php`
   - Signature: `documents:check-overdue`
   - Expire proposals: `status = published`, `valid_until IS NOT NULL`, `valid_until < today` → set `status = draft`
   - Flag invoices: `status = published`, `payment_status IN (unpaid, partially_paid)`, `due_date < today` → set `payment_status = overdue`
   - Log count of affected records
   - Idempotent: already-expired/overdue records are excluded by the query conditions
2. Register in `routes/console.php` or scheduler: `->daily()`

**Verify:** Run manually with `php artisan documents:check-overdue`. Create test proposal past `valid_until` — should become draft. Create test invoice past `due_date` — should become overdue. Run twice — no duplicate changes.

### Phase 9: Frontend (Client-Facing Views)

1. Create middleware: `DocumentAccessMiddleware`
   - Check per-document `access_username`/`access_password` first
   - Fall back to `.env` `GLOBAL_ACCESS_USERNAME`/`GLOBAL_ACCESS_PASSWORD`
   - Store authentication in session per document
2. Create routes: `/proposal/{proposal}`, `/invoice/{invoice}`
   - Use `slug` or `document_number` for URL (decide and be consistent)
   - Apply middleware
3. Create Blade views for Proposal:
   - Render all proposal sections with company branding
   - Use Tailwind CSS
   - Respect current locale (`?lang=en` or `?lang=id`)
   - Include "Download PDF" button
4. Create Blade views for Invoice:
   - Render invoice with company letterhead, items table, totals, bank details
   - Include "Download PDF" button
5. Create auth gate view (simple username/password form, no registration)

**Verify:** Client can access published document with correct credentials. Draft documents return 404. Language switcher works. PDF download works.

### Phase 10: PDF Generation

1. Create a dedicated Blade layout for PDF (print-optimized)
   - Proper margins, page breaks
   - Company letterhead with logo, colors from `color_primary`/`color_secondary`
   - Footer with company info
2. Create PDF controller/action using Browsershot:
   - Render the Blade view to HTML
   - Pass through Browsershot for PDF conversion
   - Return as download
3. Add "Generate PDF" action in Filament (both Proposal and Invoice resources)
4. Add "Download PDF" button on frontend views

**Verify:** PDF matches the HTML view. Multi-page proposals break correctly. Company branding renders. Both admin and client can download.

---

## Architecture Notes

### Document Numbering

The numbering system is critical. Read the PRD section carefully. Key points:
- Monthly reset on auto-increment
- Compound unique constraint prevents duplicates
- Admin can override the full number manually
- The suffix (default `NEW`) is per-document, not global

### Translation Strategy

Translatable JSON arrays use **per-sub-field translation**, not whole-array swapping:

```json
// CORRECT — each text sub-field has locale keys
{
  "feature_name": { "en": "Responsive", "id": "Responsif" },
  "feature_description": { "en": "Works on all devices", "id": "Berfungsi di semua perangkat" }
}

// WRONG — do not swap the entire array per locale
"features": { "en": [...], "id": [...] }
```

Non-translatable arrays (portfolios, bank, pic) use plain values.

### Proposal → Invoice Relationship

One proposal can have many invoices (initial + renewals). Client info is **copied and frozen** on the invoice at creation time, not referenced via relationship. This ensures invoice records are immutable snapshots even if the proposal changes later.

### Client Access

No user accounts for clients. Simple username/password gate per document with `.env` fallback. Session-based, no tokens. Keep it simple.

### Computed Fields

`tax_amount`, `subtotal`, `total`, `total_amount` should be computed and stored on save (not calculated on-the-fly in views). Use model `saving` or `creating` events, or Filament `afterStateUpdated` reactive form logic. Store the computed value so it can be used in dashboard queries without recalculation.

---

## Code Style

- Follow Laravel conventions (PSR-12, snake_case for DB columns, camelCase for PHP methods)
- Use Filament conventions for resources, forms, tables, actions
- Use enums for `status` and `payment_status` (PHP 8.1+ backed enums)
- JSON fields: cast to `array` in model `$casts`
- Translatable fields: use Spatie's `$translatable` array on the model
- Decimal fields: use `decimal(15, 2)` in migrations
- Always add `->nullable()` where the PRD says nullable
- Always add `->default()` where the PRD specifies a default
- Foreign keys: use `->constrained()->cascadeOnDelete()` for `company_id` and `user_id`, use `->constrained()->nullOnDelete()` for `proposal_id`

---

## File Structure (Expected)

```
app/
├── Console/Commands/
│   └── CheckOverdueDocuments.php
├── Enums/
│   ├── DocumentStatus.php          // draft, published
│   └── PaymentStatus.php           // unpaid, partially_paid, paid, overdue, cancelled
├── Filament/
│   ├── Resources/
│   │   ├── CompanyResource.php
│   │   ├── CompanyResource/Pages/
│   │   ├── ProposalResource.php
│   │   ├── ProposalResource/Pages/
│   │   ├── InvoiceResource.php
│   │   └── InvoiceResource/Pages/
│   └── Widgets/
│       ├── TotalOutstandingWidget.php
│       ├── OverdueInvoicesWidget.php
│       ├── PendingProposalsWidget.php
│       └── RevenueThisMonthWidget.php
├── Http/
│   ├── Controllers/
│   │   ├── ProposalViewController.php
│   │   ├── InvoiceViewController.php
│   │   └── PdfController.php
│   └── Middleware/
│       └── DocumentAccessMiddleware.php
├── Models/
│   ├── Company.php
│   ├── Proposal.php
│   ├── Invoice.php
│   └── User.php
├── Services/
│   └── DocumentNumberGenerator.php
database/
├── migrations/
│   ├── xxxx_create_companies_table.php
│   ├── xxxx_create_proposals_table.php
│   └── xxxx_create_invoices_table.php
resources/
├── views/
│   ├── proposals/
│   │   └── show.blade.php
│   ├── invoices/
│   │   └── show.blade.php
│   ├── pdf/
│   │   ├── proposal.blade.php
│   │   └── invoice.blade.php
│   └── auth/
│       └── document-access.blade.php
```

---

## Common Pitfalls

- **Do not use `$table->id()` shorthand if you need `bigIncrements` explicitly** — actually `id()` is fine in Laravel 12, it uses bigIncrements.
- **Spatie Translatable + JSON arrays:** The model's `$translatable` property handles top-level column translation. For translatable sub-fields inside JSON arrays, you handle this in the Filament form (translation tabs per repeater item) and store the locale keys in the JSON structure itself. Do not put JSON column names in `$translatable`.
- **Browsershot needs Node.js and Puppeteer.** In Sail, make sure the container has Node installed. In production, `npm install puppeteer` must be run.
- **Filament repeaters for JSON fields:** Use `->schema([...])` not `->relationship()`. These are JSON columns, not separate tables.
- **Document number generation race condition:** Use database-level locking (`lockForUpdate()`) when querying the max number to prevent duplicates under concurrent requests.
- **Soft deletes:** Both proposals and invoices use soft deletes. Make sure Filament resources include `SoftDeletes` trait support (restore/force delete actions).
