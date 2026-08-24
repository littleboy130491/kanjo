# Kanjo Document API — Agent Operating Manual

This is the canonical document. An integrating AI agent should **read this whole file once**, then call live catalogs/skeletons for IDs and content defaults. Do not guess field names, IDs, or dates.

**Live copy:** `GET {origin}/api/v1/guide` (`Content-Type: text/markdown`)  
**Machine index:** `GET {origin}/api/v1`  
**OpenAPI stub:** `GET {origin}/api/v1/openapi.json` (this markdown is the source of truth)

`{origin}` is the Kanjo host, for example `https://portal.imajiner.id`. All paths below are under `{origin}/api/v1`.

---

## 1. How to start (every session)

1. `GET /api/v1/guide` — this manual.
2. Authenticate every request (section 2).
3. For **lookup**: section 6 recipes, then section 5 endpoints.
4. For **create**: `GET` the matching `/skeleton`, dry-run, then create (section 7).

Never invent `company_id`, `client_id`, `proposal_id`, `invoice_id`, `service_id`, or `spk_id`.

---

## 2. Auth and transport

| | |
|---|---|
| Header | `Authorization: Bearer $DOCUMENT_API_KEY` **or** `X-Api-Key: $DOCUMENT_API_KEY` |
| JSON | `Accept: application/json` on every call except `/guide` |
| Writes | `Content-Type: application/json` |
| Rate limit | **60 requests / minute** (`X-RateLimit-Limit`, `X-RateLimit-Remaining`). On 429 wait and retry. |
| HTTPS | Required in production |

Empty or wrong key → `401`. There is no Filament login and no per-user OAuth. The server maps all creates to `DOCUMENT_API_USER_ID` (do not send `user_id`).

---

## 3. Domain model (read this before querying)

```
Company (issuing brand)
  └── Proposal, Invoice, SPK  (company_id)

Client (master record)
  ├── Proposal?   client_id optional
  ├── Invoice?    client_id optional
  ├── SPK?        client_id optional
  └── Service     belongs to one client

Service
  └── Invoice.service_id optional
        └── Invoice.proposal_id optional → Proposal

Proposal ──has many── Invoice
Proposal ──has many── SPK
```

**Frozen snapshots:** documents store `client_company`, `client_name`, `client_email`, `client_phone`, `client_address` (SPK uses `client_pic_name` / `client_pic_role`). Editing the Client later does **not** change existing documents. Search `q=` on documents uses those snapshot fields, so a proposal can match “Rovela” even when `client_id` is null.

**Services vs invoices**

| Record | Date field that means “when it comes due / renews” | Not present |
|---|---|---|
| **Service** | `renewal_date` (`YYYY-MM-DD` string) | no `due_date` |
| **Invoice** | `due_date` (`YYYY-MM-DD`) | no `renewal_date` |
| **Proposal** | `valid_until` (offer expiry, nullable = infinite) | |

**Status enums**

- Documents (`proposal` / `invoice` / `spk`): `draft` \| `published`  
  Draft public URLs 404 for clients. API **creates** are always `published`. Lookup returns both.
- Invoice `payment_status`: `unpaid` \| `partially_paid` \| `paid` \| `overdue` \| `cancelled`
- Service `status`: `on-going` \| `suspended` \| `terminated`

**What this API cannot do**

- Update or delete anything
- Create/edit companies, clients (except auto-create client on document create), or services
- Filter lists by month/year of a date field (no `renewal_month=2`). Fetch then filter in the agent
- Paginate with `offset` / `cursor` / `page`. Lists are **newest `id` first**, `limit` default **50**, max **100**
- Return document HTML bodies, notes, or passwords

If you need services with `renewal_date` in February, `GET /services?limit=100` (and `?status=` buckets if needed), then keep rows where `renewal_date` matches `YYYY-02-DD`. Newest-only list can miss older IDs; `GET /services/{id}` works for a known id.

---

## 4. Hard rules

1. Do not invent IDs. Load them from GET responses.
2. Create payloads: every `content` key in the skeleton is required. Each value is `{ "mode": "default" \| "override" \| "empty" }`.
3. `override` requires `value`. Rich text = Markdown or HTML. Repeaters = JSON arrays.
4. `default` copies Content Defaults into the document and freezes them.
5. `empty` stores blank; does not copy defaults.
6. Always `dry_run: true` before a real create.
7. Do not send `access_username`, `access_password`, `user_id`, or `status` on create.
8. Do not send `document_number` or `slug` on create (auto-generated).
9. Give humans `public_url` from the response. Do not invent document credentials (they use the site’s global document login).
10. Draft lookup rows are not client-visible until someone publishes them in Filament.

---

## 5. Endpoints

Base: `/api/v1`

### Meta

| Method | Path | Returns |
|---|---|---|
| GET | `/` | Index: name, version, `guide_url`, `openapi_url`, endpoint list |
| GET | `/guide` | This manual (markdown) |
| GET | `/openapi.json` | OpenAPI stub |

### Companies

| Method | Path | Query | Returns |
|---|---|---|---|
| GET | `/companies` | — | `{ data: Company[] }` |
| GET | `/companies/{id}` | — | `{ data: Company }` |

Company fields: `id`, `company_name`, `brand_name`, `address`, `email_1`, `phone_1`, `default_currency`, `pic[]` with `index`, `pic_name`, `pic_role`. Use `pic[].index` as `company_pic_index` when creating an SPK.

### Clients

| Method | Path | Query | Returns |
|---|---|---|---|
| GET | `/clients` | `q`, `limit` | `{ data: Client[] }` |
| GET | `/clients/{id}` | `limit` (caps each related list) | Client + related documents |

`q` matches `name`, `company`, `email` (SQL `LIKE %q%`).

Client fields: `id`, `name`, `company`, `email`, `phone`, `address`.

`GET /clients/{id}` also returns:

```json
{
  "data": { "id": 138, "name": "...", "company": "..." },
  "counts": { "proposals": 3, "invoices": 1, "services": 0, "spks": 0 },
  "proposals": [ /* ProposalSummary */ ],
  "invoices": [ /* InvoiceSummary */ ],
  "services": [ /* ServiceSummary */ ],
  "spks": [ /* SpkSummary */ ]
}
```

Related arrays are newest-first, capped by `limit` (default 50). `counts` is the full total.

### Proposals

| Method | Path | Query / body |
|---|---|---|
| GET | `/proposals` | `q`, `client_id`, `company_id`, `status`, `limit` |
| GET | `/proposals/{id}` | — |
| GET | `/proposals/skeleton` | create template |
| POST | `/proposals` | create (`dry_run` optional) |
| POST | `/proposals/{id}/invoices` | invoice from this proposal |
| POST | `/proposals/{id}/spks` | SPK from this proposal |

`q` matches `document_number`, `client_company`, `client_name`.

Proposal summary:

`type`, `id`, `document_number`, `status`, `client_id`, `company_id`, `client_company`, `client_name`, `client_email`, `client_phone`, `issue_date`, `valid_until`, `currency`, `offer_name_1`, `offer_1_price`, `offer_1_renewal_price`, `offer_name_2`, `offer_2_price`, `invoices_count`, `public_url`, `pdf_url`

`GET /proposals/{id}` adds `invoices[]`, `spks[]`, `service_ids[]` (from those invoices).

### Invoices

| Method | Path | Query / body |
|---|---|---|
| GET | `/invoices` | `q`, `client_id`, `company_id`, `proposal_id`, `service_id`, `status`, `payment_status`, `limit` |
| GET | `/invoices/{id}` | — |
| GET | `/invoices/skeleton` | create template |
| POST | `/invoices` | standalone create |

`q` matches `document_number`, `client_company`, `client_name`.

Invoice summary:

`type`, `id`, `document_number`, `status`, `payment_status`, `client_id`, `company_id`, `proposal_id`, `service_id`, `client_company`, `client_name`, `client_email`, `client_phone`, `issue_date`, `due_date`, `currency`, `subtotal`, `tax_rate`, `tax_amount`, `total`, `public_url`, `pdf_url`

### SPKs

| Method | Path | Query / body |
|---|---|---|
| GET | `/spks` | `q`, `client_id`, `company_id`, `proposal_id`, `status`, `limit` |
| GET | `/spks/{id}` | — |
| GET | `/spks/skeleton` | create template |
| POST | `/spks` | standalone create |

`q` matches `document_number`, `client_company`, `client_pic_name`.

SPK summary:

`type`, `id`, `document_number`, `status`, `client_id`, `company_id`, `proposal_id`, `client_company`, `client_pic_name`, `spk_date`, `subject`, `public_url`, `pdf_url`

### Services

| Method | Path | Query |
|---|---|---|
| GET | `/services` | `q` (name, domain), `client_id`, `status` (`on-going` \| `suspended` \| `terminated`), `limit` |
| GET | `/services/{id}` | — |

**No POST.** Services are managed in Filament.

Service summary:

`type`, `id`, `name`, `domain`, `start_date`, `renewal_date`, `price`, `currency`, `status`, `client_id`, `invoices_count`

`GET /services/{id}` adds `invoices[]` and `proposal_ids[]`.

There is **no** `due_date` on services. Recurring timing is `renewal_date`.

### Content defaults (for create)

| GET | `/content-defaults/proposal` | Current EN/ID default blobs + field keys |
| GET | `/content-defaults/spk` | Defaults + placeholder names |

---

## 6. Lookup recipes

### A. Everything for a client company (e.g. PT Rovela Karya)

```http
GET /clients?q=Rovela
GET /clients/{id}
```

Use `GET /proposals?q=Rovela` as well: that also finds documents whose **snapshot** company matches, even if `client_id` is empty.

### B. Services renewing in a calendar month (e.g. February)

There is no `renewal_month` query.

```http
GET /services?limit=100
GET /services?status=on-going&limit=100
GET /services?status=suspended&limit=100
GET /services?status=terminated&limit=100
```

Keep items where `renewal_date` is `YYYY-02-DD` (or the month you were asked for). Deduplicate by `id`. Newest-100-only can omit older services; if the human names a domain, `GET /services?q=that-domain` is better.

Invoice “due in February” is **not** a service query:

```http
GET /invoices?limit=100
```

Filter `due_date` for month `02`. Optionally `payment_status=overdue` / `unpaid`.

### C. Invoices for a service or proposal

```http
GET /invoices?service_id={id}
GET /invoices?proposal_id={id}
GET /services/{id}          # includes invoices
GET /proposals/{id}         # includes invoices and service_ids
```

---

## 7. Create

Always: skeleton → fill → `dry_run: true` → create.

Created documents are **always `published`**. Number format `{TYPE}/{NUM}/{ROMAN_MONTH}/{YY}/{SUFFIX}` e.g. `QUO/019/VIII/26/NEW`. TYPE is `QUO`, `INV`, or `SPK`. Suffix default `NEW`; from-proposal invoices use `DP` unless renewal.

### Content modes

```json
{ "mode": "default" }
{ "mode": "empty" }
{ "mode": "override", "value": { "en": "## Features\n- A", "id": "## Fitur\n- A" } }
```

- Rich text override: Markdown (`##`, `- `, `1. `) or HTML (`<p>`, `<ol>`). An HTML tag ⇒ treat as HTML; otherwise convert Markdown → HTML.
- A plain string `value` is copied to both `en` and `id`. Missing locale ⇒ empty for that locale, not filled from defaults.
- Repeaters (`add_on`, timelines): JSON arrays. A flat array is copied to both locales.

Add-on row: `{ "name": "", "description": "", "price": "" }`  
Timeline row: `{ "activity_name": "", "activity_pic": "", "activity_days": "" }`  
Invoice item: `{ "title": "", "price": 0, "description": "" }`  
Shared URL rows (`video_testimonials`, `client_logos`): `[{ "url": "https://..." }]` (not translatable).

Optional `template` on a `default` field looks up another defaults key (`ecommerce_features`, `marketing_program`, `short_project_timeline`, `business_project_timeline`, `prime_project_timeline`, `corporate_project_timeline`, `custom_project_timeline`). Payload `timeline_template` applies to both offer timelines when those fields are `default`.

**Proposal `content` keys (all required on POST):**  
`brief`, `extra_content_brief`, `core_services`, `features`, `server`, `assets`, `security`, `support`, `additional_benefit`, `add_on`, `payment`, `terms_condition`, `additional_info`, `faq`, `our_process`, `about_us`, `video_testimonials`, `client_logos`, `offer_1_project_timeline`, `offer_2_project_timeline`

**SPK `content` keys (all required):** `title`, `subject`, `content`  
`title` has no stored default (`default` ⇒ empty). `subject` / `content` `default` copy SPK defaults and resolve placeholders once:  
`spk_number`, `spk_date`, `client_company`, `client_pic_name`, `client_pic_role`, `client_address`, `company_name`, `company_pic_name`, `company_pic_role`, `company_address`, `proposal_number`, `proposal_date`, `offer_name`, `offer_price`, `offer_name_1`, `offer_price_1`, `offer_name_2`, `offer_price_2`, `offer_timeline`, `offer_timeline_1`, `offer_timeline_2`, `subject`

**Invoice content:** no defaults catalog. Standalone POST requires `items` and `content.additional_info` with mode `override` or `empty` only.

### Client on create

Send `client_id` **or** `client`:

```json
"client": {
  "company": "PT Contoh",
  "name": "Budi",
  "email": "budi@contoh.test",
  "phone": "08123456789",
  "address": "Jl. Contoh 1",
  "pic_role": "Director"
}
```

`company` and `name` required when creating a client. Email/phone/address optional. `pic_role` is for SPK `client_pic_role`. No `client_id` ⇒ insert a new Client (no match-by-email). Optional `client` plus `client_id` overrides snapshot fields on the **document only**.

### Other create defaults

- `company_id` required on standalone creates
- `service_id` optional on invoices only; services are not auto-created
- `activate_translation` default `false`
- Currency: company `default_currency` or `IDR`
- Proposal `tax_rate` default `11`; standalone invoice default `0`; from-proposal copies the proposal
- `issue_date` / `spk_date` default today; proposal `valid_until` and invoice `due_date` default +30 days

### Dry-run

```json
{ "dry_run": true }
```

No writes. `200` + `valid`, `would_create`, `resolved_content_preview`, `warnings`. Invalid → `422`. Then POST again without `dry_run` (or `false`).

### Create success

```json
{
  "data": {
    "type": "proposal",
    "id": 123,
    "document_number": "QUO/005/VIII/26/NEW",
    "status": "published",
    "public_url": "https://portal.imajiner.id/proposal/...",
    "pdf_url": "https://portal.imajiner.id/proposal/.../pdf",
    "client_id": 88
  }
}
```

### From a proposal

**Invoice** `POST /proposals/{id}/invoices`  
Copies frozen client snapshot. Default: one item from Offer 1, suffix `DP`, unpaid, published. Optional `offer` `1|2`, `renewal: true` (title `{offer_name} — Renewal`, suffix `NEW`). Optional `items` replace generated items. `content.additional_info` may be `default` (copy proposal), `override`, or `empty`.

**SPK** `POST /proposals/{id}/spks`  
Copies client/company snapshots. `company_pic_index` (0-based from company `pic`) or `company_pic_name` + `company_pic_role`. Omit index ⇒ first PIC. Content modes still required; `default` interpolates placeholders. Optional `offer` / `offer_index` `1|2`.

### Minimal proposal create (after skeleton)

```http
GET /companies
GET /proposals/skeleton
POST /proposals
```

```json
{
  "dry_run": true,
  "company_id": 1,
  "client": {
    "company": "PT Contoh",
    "name": "Budi",
    "email": "budi@contoh.test",
    "phone": "0812...",
    "address": "..."
  },
  "offer_name_1": "Business Package",
  "offer_1_price": 25000000,
  "offer_1_renewal_price": 3000000,
  "content": { "...every key from skeleton..." }
}
```

---

## 8. Errors

| Status | Meaning | Agent action |
|---|---|---|
| 401 | Missing/invalid key, or API key not configured | Stop. Do not retry the same key. |
| 404 | Unknown id | Reload catalogs; do not guess another id. |
| 405 | Method not allowed | You hit a create-only path with GET or the reverse. Check this guide. |
| 422 | Validation | Read `errors`, `missing_content_fields`, `hint`. Fetch `/proposals/skeleton` (or invoices/spks). Do not retry the same body. |
| 429 | Rate limited | Wait; retry. Prefer list filters over scanning every `{id}`. |
| 503 | `DOCUMENT_API_USER_ID` missing or not a real user | Server config; cannot fix from the payload. |

---

## 9. Checklist before you answer a human

- [ ] IDs came from GET responses  
- [ ] Lookup used snapshot `q=` when the question is a **company name on documents**  
- [ ] Service timing used `renewal_date`, invoice timing used `due_date`  
- [ ] Create used skeleton + dry-run  
- [ ] You did not claim a draft `public_url` is client-visible  
- [ ] You did not invent passwords  
