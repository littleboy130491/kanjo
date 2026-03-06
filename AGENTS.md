# AGENTS.md

## Project Overview

This is a quotation and invoice management web app for a web design agency with multiple brands. Read `PRD.md` in the project root before doing anything. The PRD is the single source of truth for all requirements, field definitions, and business logic.

Do not assume or invent requirements. If something is not in the PRD, ask.

---

## Critical Rules

1. Read `PRD.md` first. Every task must align with the PRD.
2. Work in small steps. One migration, one model, one resource, one action at a time.
3. Commit after every meaningful change. Never bundle unrelated changes.
4. Test before committing. Run migrations, check syntax, verify admin panel loads.
5. Do not refactor ahead of need. Build only what PRD specifies.
6. Ask before deviating. If PRD does not specify a decision, ask.
7. If unsure about Filament APIs or patterns, verify against official Filament 5 docs first. If MCP reference docs are available, use them as secondary context.

---

## Tech Stack

| Layer | Technology | Notes |
|---|---|---|
| Framework | Laravel 12 | |
| Admin Panel | Filament 5 | All CRUD, actions, dashboard |
| CSS | Tailwind CSS | Frontend views + PDF styling |
| Dev Environment | Laravel Sail or Herd | Sail preferred for Browsershot (needs Node.js) |
| Translations | Spatie Laravel Translatable | Dual language (EN/ID) |
| Media | Curator (Filament plugin) | Logos, portfolio images, PIC signatures |
| PDF | Browsershot | Headless Chrome via Puppeteer |

---

## Commit Convention

Format: `type(scope): description`

```text
feat(migration): create companies table
feat(model): add Company model with translatable fields
feat(resource): add CompanyResource with CRUD
feat(migration): create clients table
feat(model): add Client model with relationships
feat(resource): add ClientResource with CRUD
feat(migration): create services table
feat(model): add Service model with relationships
feat(resource): add ServiceResource with CRUD
feat(migration): create proposals table
feat(model): add Proposal model with frozen client snapshot fields
feat(resource): add ProposalResource table columns and filters
feat(resource): add ProposalResource form with repeaters
feat(migration): create invoices table
feat(model): add Invoice model with frozen client snapshot fields
feat(action): add convert-to-invoice action on Proposal
feat(action): add create-client quick action on Proposal and Invoice
feat(action): add create-service quick action on Proposal and Invoice
feat(command): add documents:check-overdue artisan command
fix(model): fix proposal numbering auto-increment logic
refactor(resource): extract shared document number logic
```

Keep commits atomic. One logical change per commit.

---

## Development Phases

Execute these phases in order. Complete each phase fully before moving to the next.

### Phase 1: Project Setup

1. Install Laravel 12 with Sail
2. Install and configure Filament 5
   - Use official install flow for v5 panel builder:
     - `composer require filament/filament:"^5.0"` (PowerShell fallback: `~5.0`)
     - `php artisan filament:install --panels`
3. Install Spatie Laravel Translatable
4. Install Curator for Filament
5. Install Browsershot
6. Configure `.env` with `GLOBAL_ACCESS_USERNAME` and `GLOBAL_ACCESS_PASSWORD`
7. Configure Filament panel provider (branding, navigation groups)
8. Configure supported locales: `en`, `id`

Verify: Admin panel loads at `/admin`, login works.

### Phase 2: Company Entity

1. Create `companies` migration exactly per PRD
2. Create `Company` model (`$casts` for `bank`, `pic`; translatable `footer_text`)
3. Create `CompanyResource` with repeaters for `bank` and `pic`, Curator media picker for `logo`
4. Seed at least one test company

Verify: CRUD works, logo uploads work, repeaters work.

### Phase 3: Client Entity

1. Create `clients` migration exactly per PRD:
   - `name`, `company`, `email`, `phone`
   - `notes` JSON array (non-translatable)
   - soft deletes
2. Create `Client` model:
   - `$casts` for `notes`
   - relationships: `hasMany(Proposal)`, `hasMany(Invoice)`, `hasMany(Service)`
3. Create `ClientResource` with full CRUD

Verify: Can create/edit/delete clients, notes JSON works.

### Phase 4: Service Entity

1. Create `services` migration exactly per PRD:
   - `name`, `domain`, `start_date`, `renewal_date`
   - `status` enum: `terminated`, `on-going`, `suspended`
   - `notes` JSON array (non-translatable)
   - `client_id` FK
   - soft deletes
2. Create `Service` model:
   - `$casts` for `notes`, `status`
   - relationships: `belongsTo(Client)`, `hasMany(Proposal)`, `hasMany(Invoice)`
3. Create `ServiceResource` with full CRUD

Verify: CRUD works, client relation works, status filtering works.

### Phase 5: Proposal Entity

1. Create `proposals` migration exactly per PRD:
   - Use `decimal(15, 2)` for decimal fields
   - Use `json` for JSON fields
   - Add soft deletes
   - Add foreign keys for `user_id`, `company_id`, `client_id` (nullable), `service_id` (nullable)
   - Add frozen snapshot fields: `client_company`, `client_name`, `client_email`, `client_phone`
2. Create `Proposal` model:
   - `$casts` for JSON fields and enums
   - Spatie translatable setup for translatable fields only (per PRD)
   - relationships: `belongsTo(Company)`, `belongsTo(User)`, `belongsTo(Client)` nullable, `belongsTo(Service)` nullable, `hasMany(Invoice)`
   - `booted()` method for auto-generating `document_number`
3. Create `ProposalResource` table:
   - Columns: `document_number`, `client_company`, `client_name`, `offer_1_price`, `status`, `issue_date`, `invoices_count`
   - Filters: `status`, `company_id`, `has_invoice`, `issue_date`, `created_at`, `valid_until`
   - Searchable: `client_name`, `client_company`, `document_number`
   - Default sort: `created_at` desc
4. Create `ProposalResource` form:
   - Client section uses snapshot fields (`client_company`, `client_name`, `client_email`, `client_phone`)
   - Add optional `client_id` selector for reference only
   - Add optional `service_id` selector
   - Include all PRD sections and repeaters
5. Snapshot rule:
   - When proposal is created from selected client, copy client values into snapshot fields
   - Proposal snapshot fields do not auto-sync when client record changes

Verify: Full proposal CRUD works, repeaters/translations work, number auto-generates, snapshot behavior is correct.

### Phase 6: Invoice Entity

1. Create `invoices` migration exactly per PRD:
   - Add foreign keys for `proposal_id` (nullable), `user_id`, `company_id`, `client_id` (nullable), `service_id` (nullable)
   - Add frozen snapshot fields: `client_company`, `client_name`, `client_email`, `client_phone`
   - Soft deletes
2. Create `Invoice` model:
   - `$casts`, translatable fields, relationships
   - relationships: `belongsTo(Proposal)` nullable, `belongsTo(Company)`, `belongsTo(User)`, `belongsTo(Client)` nullable, `belongsTo(Service)` nullable
   - auto-generate `document_number` on creating
3. Create `InvoiceResource` table:
   - Columns: `document_number`, `client_company`, `client_name`, `total`, `payment_status`, `status`, `issue_date`, `due_date`, `proposal`
   - Filters: `status`, `payment_status`, `company_id`, `has_proposal`, `issue_date`, `due_date`, `created_at`
   - Searchable: `client_name`, `client_company`, `document_number`
4. Create `InvoiceResource` form:
   - Client section uses snapshot fields plus optional `client_id` reference
   - Optional `service_id`
   - Items repeater, tax/totals, payment status, notes
5. Snapshot rule:
   - Invoice snapshot fields do not auto-sync when client record changes

Verify: Invoice CRUD works, filters/search work, snapshot behavior is correct.

### Phase 7: Document Numbering Logic

1. Create shared `DocumentNumberGenerator`
2. Integrate in Proposal and Invoice `creating` events
3. Manual override: skip generation if `document_number_override` is true
4. Validate unique on `type + raw_number + month + year`

Verify: increment and monthly reset work, manual override works, duplicates rejected.

### Phase 8: Custom Actions

1. Convert to Invoice (Proposal action):
   - Create Invoice with frozen snapshot fields copied from Proposal
   - Copy `client_id` and `service_id` as optional references
   - Generate item from Offer 1
   - Set `proposal_id`, `status = draft`, `payment_status = unpaid`
2. Create Renewal Invoice (Proposal action):
   - Same as above but use `offer_1_renewal_price`
3. Duplicate Proposal
4. Duplicate Invoice
5. Mark as Paid
6. View Proposal (Invoice action)
7. Create Client (Proposal and Invoice quick action):
   - Build client from snapshot fields
   - Save new `client_id` without overwriting existing snapshot content
8. Create Service (Proposal and Invoice quick action):
   - Build service and auto-link selected `client_id`

Verify: each action works end-to-end with snapshot rules preserved.

### Phase 9: Dashboard Widgets

1. Total Outstanding
2. Overdue Invoices
3. Pending Proposals
4. Revenue This Month

Verify: widget values match seeded data.

### Phase 10: Scheduled Command

1. Create `documents:check-overdue`
2. Expire proposals past `valid_until`
3. Mark overdue invoices past `due_date`
4. Schedule daily

Verify: idempotent behavior.

### Phase 11: Frontend (Client-Facing Views)

1. `DocumentAccessMiddleware`
2. Routes for proposal/invoice views
3. Proposal blade
4. Invoice blade
5. Auth gate view

Verify: published docs accessible with credentials, draft docs 404, language switch works.

### Phase 12: PDF Generation

1. Dedicated print layout
2. Browsershot controller/action
3. Filament Generate PDF actions
4. Frontend Download PDF buttons

Verify: PDF output matches HTML and branding.

---

## Architecture Notes

### Document Numbering

- Monthly reset on auto-increment
- Compound unique constraint prevents duplicates
- Admin can override full number
- Suffix default `NEW` is per-document

### Translation Strategy

Translatable JSON arrays use per-sub-field translation, not whole-array swapping.

### Proposal -> Invoice Relationship

- One proposal can have many invoices.
- Client data on proposal and invoice is a frozen snapshot (`client_*` fields).
- Existing documents must not change when the client master record is edited.
- `client_id` is optional reference for navigation and convenience only.

### Client Access

No user accounts for clients. Use username/password gate per document with `.env` fallback.

### Computed Fields

`tax_amount`, `subtotal`, `total`, `total_amount` are computed and stored on save.

---

## Filament 5 Conventions

Follow these conventions unless PRD explicitly says otherwise:

1. Generate scaffolding with Filament commands instead of hand-rolling:
   - `php artisan make:filament-resource ModelName`
   - add `--soft-deletes` when entity uses soft deletes
2. Keep the v5 resource structure:
   - resource class in `app/Filament/Resources/*Resource.php`
   - pages in `app/Filament/Resources/*Resource/Pages/`
   - by default, Filament may generate separate `Schemas/*Form.php` and `Tables/*Table.php`; keep this pattern if generated
3. Use v5 schema/table APIs:
   - form schema methods should use `Filament\Schemas\Schema`
   - table methods should use `Filament\Tables\Table`
   - prefer `->recordActions([...])` / `->toolbarActions([...])` patterns used by v5 docs
4. Keep page routing in `getPages()` using Filament page classes (`List*`, `Create*`, `Edit*`, `View*`) and route definitions.
5. Panel configuration must live in a panel provider (`AdminPanelProvider`) via `panel(Panel $panel): Panel`.
6. Navigation, filters, actions, and widgets should use first-party Filament components before custom implementations.
7. For plugin usage (for example Curator), follow plugin docs for Filament 5 compatibility before implementation.

Verification workflow when uncertain:
- First source: official docs at `https://filamentphp.com/docs/5.x`
- Second source: installed MCP docs/resources when available in the environment
- If neither is available, state assumptions explicitly in commit message and PR notes

---

## Code Style

- Follow Laravel conventions (PSR-12, snake_case DB columns, camelCase methods)
- Follow Filament 5 naming and file conventions from generated scaffolding
- Use enums for statuses
- JSON fields cast to `array`
- Spatie translatable only for true translatable fields
- Decimal fields use `decimal(15, 2)`
- Respect PRD nullable/default requirements
- Foreign keys:
  - `company_id`, `user_id` => `constrained()->cascadeOnDelete()`
  - `proposal_id`, `client_id`, `service_id` => `constrained()->nullOnDelete()` when nullable

---

## Frontend Rules (Proposal & Invoice Documents)

Apply these rules when building or updating `resources/views/proposals/show.blade.php` and `resources/views/invoices/show.blade.php`:

1. Use Tailwind CSS as the default styling approach.
2. If more than one element shares the same style, extract it into a reusable CSS class (do not duplicate long utility strings everywhere).
3. Do not create a custom document CSS class for a style used by only one element in one view. Keep single-use styling inline with Tailwind utilities unless the class is required for print behavior, JavaScript hooks, or a clearly named document region/component.
4. In document stylesheet files, use Tailwind `@apply` wherever possible for reusable classes.
5. Treat documents as print-first output:
   - CSS must be compatible with print/PDF rendering (Browsershot/Chromium).
   - Prefer stable layout primitives and explicit spacing.
   - Include/maintain `@media print` behavior for page breaks, table headers, and overflow handling.
   - Avoid depending on effects that are unreliable in PDF output.

---

## File Structure (Expected)

```text
app/
  Console/Commands/
    CheckOverdueDocuments.php
  Enums/
    DocumentStatus.php
    PaymentStatus.php
    ServiceStatus.php
  Filament/
    Resources/
      CompanyResource.php
      CompanyResource/Pages/
      ClientResource.php
      ClientResource/Pages/
      ServiceResource.php
      ServiceResource/Pages/
      ProposalResource.php
      ProposalResource/Pages/
      InvoiceResource.php
      InvoiceResource/Pages/
    Widgets/
      TotalOutstandingWidget.php
      OverdueInvoicesWidget.php
      PendingProposalsWidget.php
      RevenueThisMonthWidget.php
  Http/
    Controllers/
      ProposalViewController.php
      InvoiceViewController.php
      PdfController.php
    Middleware/
      DocumentAccessMiddleware.php
  Models/
    Company.php
    Client.php
    Service.php
    Proposal.php
    Invoice.php
    User.php
  Services/
    DocumentNumberGenerator.php
database/
  migrations/
    xxxx_create_companies_table.php
    xxxx_create_clients_table.php
    xxxx_create_services_table.php
    xxxx_create_proposals_table.php
    xxxx_create_invoices_table.php
resources/
  views/
    proposals/show.blade.php
    invoices/show.blade.php
    pdf/proposal.blade.php
    pdf/invoice.blade.php
    auth/document-access.blade.php
```

---

## Common Pitfalls

- Do not auto-sync `client_*` snapshot fields from `client_id` after document creation.
- Spatie translatable does not automatically translate nested JSON array structures.
- Browsershot requires Node.js and Puppeteer.
- Use repeater `->schema([...])` for JSON columns, not `->relationship()`.
- Prevent document-number race conditions with DB locking.
- Proposals, invoices, clients, and services use soft deletes.
