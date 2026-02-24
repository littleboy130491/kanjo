# Activity 06 — Filament Tables, Filters & Search

## Goal
Configure table columns, filters, and search for `ProposalResource` and `InvoiceResource`.

---

## Proposals Table

### Columns
| Column | Type | Notes |
|---|---|---|
| `document_number` | TextColumn | searchable |
| `client_company` | TextColumn | searchable |
| `client_name` | TextColumn | searchable |
| `offer_1_price` | TextColumn | money format |
| `status` | BadgeColumn | `draft` = gray, `published` = green |
| `issue_date` | DateColumn | sortable |
| `invoices_count` | TextColumn | count badge via `withCount('invoices')` |

### Filters
| Filter | Type | Logic |
|---|---|---|
| `status` | SelectFilter | `draft` / `published` |
| `company_id` | SelectFilter | Issuing company |
| `has_invoice` | TernaryFilter | `whereHas('invoices')` / `whereDoesntHave('invoices')` |
| `issue_date` | DateRangeFilter | Between two dates |
| `created_at` | DateRangeFilter | Between two dates |
| `valid_until` | DateRangeFilter | Useful for finding expired proposals |

### Search
Searchable columns: `document_number`, `client_name`, `client_company`

---

## Invoices Table

### Columns
| Column | Type | Notes |
|---|---|---|
| `document_number` | TextColumn | searchable |
| `client_company` | TextColumn | searchable |
| `client_name` | TextColumn | searchable |
| `total` | TextColumn | money format |
| `payment_status` | BadgeColumn | color-coded (see below) |
| `status` | BadgeColumn | `draft` = gray, `published` = green |
| `issue_date` | DateColumn | sortable |
| `due_date` | DateColumn | sortable |
| `proposal` | TextColumn | link to source proposal; hidden if no `proposal_id` |

### Payment Status Badge Colors
| Status | Color |
|---|---|
| `unpaid` | warning (yellow) |
| `partially_paid` | info (blue) |
| `paid` | success (green) |
| `overdue` | danger (red) |
| `cancelled` | gray |

### Filters
| Filter | Type | Logic |
|---|---|---|
| `status` | SelectFilter | `draft` / `published` |
| `payment_status` | SelectFilter | All 5 statuses |
| `company_id` | SelectFilter | Issuing company |
| `has_proposal` | TernaryFilter | `whereNotNull('proposal_id')` / `whereNull('proposal_id')` |
| `issue_date` | DateRangeFilter | Between two dates |
| `due_date` | DateRangeFilter | Between two dates |
| `created_at` | DateRangeFilter | Between two dates |

### Search
Searchable columns: `document_number`, `client_name`, `client_company`

---

## Implementation Notes
- Use `->money('IDR')` or custom formatter for price columns
- For date range filters, use `Filter::make()->form([DatePicker, DatePicker])->query()`
- `invoices_count` requires `withCount('invoices')` in the Eloquent query via `modifyQueryUsing` or eager loading in the resource

## Acceptance Criteria
- All columns render with correct data
- Each filter narrows results as described
- Searching by `document_number`, `client_name`, or `client_company` returns correct rows
- Invoice `payment_status` badge shows correct color per status
