# Activity 05 — Filament Resources (CRUD)

## Goal
Build the six Filament admin resources: Company, Client, Service, Proposal, Invoice, User.

---

## Resource 1: `CompanyResource`

**File:** `app/Filament/Resources/CompanyResource.php`

### Form Fields
- `company_name` — TextInput, required
- `brand_name` — TextInput, required
- `logo` — CuratorPicker (media upload)
- `address` — Textarea
- `email_1` — TextInput, email, required
- `email_2` — TextInput, email, nullable
- `phone_1` — TextInput, required
- `phone_2` — TextInput, nullable
- `tax_id` — TextInput (NPWP)
- `website` — TextInput, URL
- `default_currency` — Select: `IDR`, `USD`, `EUR`
- `color_primary` — TextInput (hex) or ColorPicker
- `color_secondary` — TextInput (hex) or ColorPicker
- `footer_text` — Textarea, wrapped in `TranslatableFields` (en + id tabs)
- `bank` — Repeater with sub-fields: `bank_name`, `account_name`, `account_number`
- `pic` — Repeater with sub-fields: `pic_name`, `pic_role`, `pic_sign` (CuratorPicker)

### Table Columns
- `brand_name`, `company_name`, `email_1`, `phone_1`, `default_currency`

---

## Resource 2: `ClientResource`

**File:** `app/Filament/Resources/ClientResource.php`

### Form Fields
- `name` — TextInput, required (contact person)
- `company` — TextInput, required (client's company name)
- `email` — TextInput, email, required
- `phone` — TextInput, required
- `notes` — Repeater with sub-field: `note` (non-translatable JSON array)

### Table Columns
- `name`, `company`, `email`, `phone`
- `proposals_count` — count badge
- `invoices_count` — count badge
- `services_count` — count badge

### Soft Deletes
Generate with `--soft-deletes` flag.

---

## Resource 3: `ServiceResource`

**File:** `app/Filament/Resources/ServiceResource.php`

### Form Fields
- `name` — TextInput, required
- `domain` — TextInput, nullable (website URL)
- `start_date` — TextInput, nullable (when service became active)
- `renewal_date` — TextInput, nullable (date and month for renewal)
- `status` — Select: `terminated`, `on-going`, `suspended`
- `client_id` — Select (relationship to Client)
- `notes` — Repeater with sub-field: `note` (non-translatable JSON array)

### Table Columns
- `name`, `domain`, `status` (badge), `client.company`, `renewal_date`

### Filters
- `status` — SelectFilter
- `client_id` — SelectFilter

### Soft Deletes
Generate with `--soft-deletes` flag.

---

## Resource 4: `ProposalResource`

**File:** `app/Filament/Resources/ProposalResource.php`

### Form Fields (grouped into sections/tabs)

**Section: Document Info**
- `document_number` — TextInput, readonly (auto-generated), shown after creation
- `document_number_suffix` — TextInput, default `NEW`
- `document_number_override` — Toggle; when enabled, unlock suffix editing
- `company_id` — Select (relationship)
- `status` — Select: `draft`, `published`
- `issue_date` — DatePicker, default today
- `valid_until` — DatePicker, nullable (null = infinite)

**Section: Client Info**
- `client_id` — Select (relationship to Client), nullable, optional reference only
- `client_company`, `client_name`, `client_email`, `client_phone` — TextInput (frozen snapshot fields)
- When `client_id` is selected on create, auto-populate the snapshot fields from the client record

**Section: Service**
- `service_id` — Select (relationship to Service), nullable, optional reference

**Section: Financials**
- `currency` — Select
- `tax_rate` — TextInput (numeric), default `11`
- `offer_name_1`, `offer_1_price`, `offer_1_original_price`, `offer_1_renewal_price`, `offer_1_original_renewal_price`
- `offer_name_2`, `offer_2_price`, `offer_2_original_price`, `offer_2_renewal_price`, `offer_2_original_renewal_price` (nullable group)

**Section: Content (Translatable)**
All wrapped in `TranslatableFields` with `en` + `id` tabs:
- `brief` — Repeater (e.g., single JSON item with `content` field)
- `core_services` — Repeater
- `features` — Repeater: `feature_name`, `feature_description`
- `server` — Repeater
- `assets` — Repeater
- `security` — Repeater
- `support` — Repeater
- `additional_benefit` — Repeater
- `add_on` — Repeater: `name`, `description`, `price`
- `payment` — Repeater: `info`, `down_payment_amount`
- `terms_condition` — Repeater: `title`, `description`
- `offer_1_project_timeline` — Repeater: `activity_name`, `activity_pic`, `activity_days`
- `offer_2_project_timeline` — Repeater: same structure (nullable)

**Section: Portfolios (non-translatable)**
- `portfolios` — Repeater: `portfolio_name`, `portfolio_image_url`, `portfolio_link`

**Section: Internal**
- `notes` — Textarea
- `access_username` — TextInput
- `access_password` — TextInput (password field, hashed on save)

---

## Resource 5: `InvoiceResource`

**File:** `app/Filament/Resources/InvoiceResource.php`

### Form Fields

**Section: Document Info**
- `document_number` — readonly
- `document_number_suffix` — TextInput
- `document_number_override` — Toggle
- `company_id` — Select
- `proposal_id` — Select (nullable, relationship)
- `status` — Select: `draft`, `published`
- `payment_status` — Select: `unpaid`, `partially_paid`, `paid`, `overdue`, `cancelled`
- `issue_date` — DatePicker
- `due_date` — DatePicker

**Section: Client Info**
- `client_id` — Select (relationship to Client), nullable, optional reference only
- `client_company`, `client_name`, `client_email`, `client_phone` — TextInput (frozen snapshot fields)

**Section: Service**
- `service_id` — Select (relationship to Service), nullable

**Section: Items (Translatable)**
- `items` — Repeater wrapped in `TranslatableFields`: `title`, `description`, `price`

**Section: Financials**
- `currency`, `tax_rate`
- `subtotal`, `tax_amount`, `total` — readonly, computed
- `paid_amount`, `payment_method`, `paid_at`

**Section: Internal**
- `notes`, `access_username`, `access_password`

---

## Resource 6: `UserResource`

**File:** `app/Filament/Resources/UserResource.php`

Standard Filament user management:
- `name`, `email`, `password`
- Table: `name`, `email`, `created_at`

---

## Client Snapshot Behavior

When creating a Proposal or Invoice with a selected `client_id`:
1. Auto-populate `client_company`, `client_name`, `client_email`, `client_phone` from the selected Client record
2. These snapshot fields are independently editable — they do NOT auto-sync after creation
3. The `client_id` remains as an optional reference for navigation only

---

## Acceptance Criteria
- All 6 resources accessible in Filament admin sidebar
- Company, Client, Service, Proposal, Invoice CRUD operations work without errors
- Client snapshot fields auto-populate when selecting a client on create
- Translatable fields show language tabs for `en` and `id`
- Curator media picker works for logo and PIC signature fields
- Service resource shows client relationship and status filter
