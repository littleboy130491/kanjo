# Activity 07 — Custom Filament Actions

## Goal
Implement all custom table/record actions on Proposals and Invoices.

---

## Proposal Actions

### 1. Convert to Invoice

**Trigger:** Table row action on Proposal
**Condition:** Only shown when `status = 'published'`

**Logic:**
1. Copy fields to new Invoice:
   - `client_company`, `client_name`, `client_email`
   - `company_id`, `user_id`
   - `currency`, `tax_rate`
   - `access_username`, `access_password`
2. Generate `document_number` via `DocumentNumberService::generate('INV', now())`
3. Set `issue_date` = today, `due_date` = today + 30 days
4. Create first invoice item:
   - `title` = `offer_name_1`
   - `price` = `offer_1_price`
   - `description` = '' (admin fills in)
5. Set `status` = `draft`, `payment_status` = `unpaid`
6. Set `proposal_id` = source proposal ID
7. Redirect to the new Invoice edit page

**No confirmation modal** — one click, immediate action.

---

### 2. Create Renewal Invoice

**Trigger:** Table row action on Proposal
**Condition:** Only shown when `offer_1_renewal_price` is not null

**Logic:** Same as "Convert to Invoice", except:
- Item `price` = `offer_1_renewal_price` instead of `offer_1_price`
- Item `title` = `"{offer_name_1} — Renewal"`
- A proposal can generate multiple renewal invoices (one-to-many)

---

### 3. Duplicate Proposal

**Trigger:** Table row action on Proposal

**Logic:**
1. Clone all fields from source proposal
2. Generate a **new** `document_number` via `DocumentNumberService`
3. Set `status` = `draft`
4. Redirect to the new Proposal edit page

---

### 4. Generate PDF (Proposal)

**Trigger:** Table row action + view page header action

**Logic:**
1. Render the proposal's HTML frontend view at the current locale
2. Pass through Browsershot to produce a PDF
3. Return as a file download response

---

## Invoice Actions

### 5. Duplicate Invoice

**Trigger:** Table row action

**Logic:**
1. Clone all fields from source invoice
2. Generate a **new** `document_number` via `DocumentNumberService`
3. Set `status` = `draft`, `payment_status` = `unpaid`
4. Reset `paid_amount` = 0, `paid_at` = null, `payment_method` = null
5. Redirect to the new Invoice edit page

---

### 6. Generate PDF (Invoice)

Same as Proposal PDF generation, using the invoice frontend view.

---

### 7. Mark as Paid

**Trigger:** Table row action
**Condition:** `payment_status` is `unpaid`, `partially_paid`, or `overdue`

**Modal fields:**
- `paid_amount` — decimal, required
- `payment_method` — TextInput, required
- `paid_at` — DateTimePicker, default now

**Logic on submit:**
- Update `paid_amount`, `payment_method`, `paid_at`
- If `paid_amount >= total`: set `payment_status` = `paid`
- Else: set `payment_status` = `partially_paid`

---

### 8. View Proposal

**Trigger:** Table row action + view page
**Condition:** Only visible when `proposal_id` is not null

**Logic:** Redirect to the source ProposalResource edit/view page.

---

## Acceptance Criteria
- "Convert to Invoice" creates invoice with correct field snapshot
- "Create Renewal Invoice" uses renewal price and appends " — Renewal" to title
- Duplicate actions produce new records with new document numbers
- "Mark as Paid" correctly sets `paid` vs `partially_paid` based on amount
- PDF download returns a valid PDF file
- "View Proposal" only appears on invoices with a linked proposal
