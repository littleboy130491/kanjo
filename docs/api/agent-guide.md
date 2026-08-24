# Kanjo Document API — Agent Guide

Create published proposals, invoices, and SPKs without a Filament login.

**Base:** `/api/v1`  
**Auth:** `Authorization: Bearer $DOCUMENT_API_KEY` or `X-Api-Key: $DOCUMENT_API_KEY`  
**Writes:** create only. Status is always `published`.  
**Reads:** catalogs, skeletons, and lookup of existing clients, proposals, invoices, SPKs, and services.

## Find existing records

To find documents for a client company (example: PT Rovela Karya):

1. `GET /api/v1/clients?q=Rovela` — pick `id`.
2. `GET /api/v1/clients/{id}` — related `proposals`, `invoices`, `services`, `spks`, and `counts`.
3. Or search documents directly: `GET /api/v1/proposals?q=Rovela` (matches frozen `client_company` / `client_name` / `document_number`, including docs with no `client_id`).
4. Services: `GET /api/v1/services?client_id={id}` or `?q=domain`. Invoices for a service: `GET /api/v1/invoices?service_id={id}`.

Do not invent IDs. List responses are summaries (no HTML body, no passwords). Default `limit` 50, max 100.

## Required loop

1. `GET /api/v1/guide` — this document.
2. `GET /api/v1/companies` — pick `company_id` (and PIC index for SPK).
3. `GET /api/v1/clients?q=` — reuse `client_id`, or omit it and a Client is created from snapshot fields.
4. `GET /api/v1/{proposals|invoices|spks}/skeleton` — required payload with every content mode filled.
5. Edit the skeleton. Change modes to `override` or `empty` only where needed.
6. `POST` the same path with `"dry_run": true`. Fix any 422.
7. `POST` again with `"dry_run": false` (or omit it) to publish.
8. Give the human `public_url` from the success body. Do not invent credentials.

## Rules you must not break

1. Never invent `company_id` or `client_id`. Load them from GET catalogs.
2. Never omit a content field listed in the skeleton. Each value is `{ "mode": "default" | "override" | "empty" }`.
3. `mode: "override"` requires `value`. Rich text may be Markdown or HTML; the server stores HTML. Repeaters stay JSON.
4. `mode: "default"` copies current Content Defaults into the document and freezes them. Later default edits do not change this document.
5. `mode: "empty"` stores blank. It does not copy defaults.
6. Always dry-run first. Only create when the preview looks right.
7. Client snapshot fields on the document are frozen. Creating or editing a Client later does not change existing docs.
8. Do not send `access_username` / `access_password`. Public pages use the app’s global document credentials.
9. Do not send `user_id` or `status`. Author is `DOCUMENT_API_USER_ID`. Status is `published`.
10. Document numbers and slugs are generated. Do not set them.

## Endpoints

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/v1` | Index |
| GET | `/api/v1/guide` | This guide (markdown) |
| GET | `/api/v1/openapi.json` | OpenAPI |
| GET | `/api/v1/companies` | Issuing companies + PIC list |
| GET | `/api/v1/companies/{id}` | One company + PIC list |
| GET | `/api/v1/clients` | Search clients (`?q=` name, company, email) |
| GET | `/api/v1/clients/{id}` | Client + related proposals, invoices, services, SPKs |
| GET | `/api/v1/proposals` | Search proposals (`?q=`, `client_id`, `company_id`, `status`) |
| GET | `/api/v1/proposals/{id}` | Proposal summary + invoices, SPKs, service ids |
| GET | `/api/v1/invoices` | Search invoices (`?q=`, `client_id`, `service_id`, `proposal_id`) |
| GET | `/api/v1/invoices/{id}` | Invoice summary |
| GET | `/api/v1/spks` | Search SPKs (`?q=`, `client_id`, `proposal_id`) |
| GET | `/api/v1/spks/{id}` | SPK summary |
| GET | `/api/v1/services` | Search services (`?q=` name/domain, `client_id`, `status`) |
| GET | `/api/v1/services/{id}` | Service + invoices and proposal ids |
| GET | `/api/v1/content-defaults/proposal` | Current proposal defaults |
| GET | `/api/v1/content-defaults/spk` | Current SPK defaults + placeholders |
| GET | `/api/v1/proposals/skeleton` | Proposal payload skeleton |
| GET | `/api/v1/invoices/skeleton` | Invoice payload skeleton |
| GET | `/api/v1/spks/skeleton` | SPK payload skeleton |
| POST | `/api/v1/proposals` | Create proposal |
| POST | `/api/v1/invoices` | Create standalone invoice |
| POST | `/api/v1/spks` | Create standalone SPK |
| POST | `/api/v1/proposals/{id}/invoices` | Invoice from proposal |
| POST | `/api/v1/proposals/{id}/spks` | SPK from proposal |

## Content modes

```json
{ "mode": "default" }
{ "mode": "empty" }
{ "mode": "override", "value": { "en": "## Features\n- A", "id": "## Fitur\n- A" } }
```

- Override rich text: Markdown (`##`, `- `, `1. `) **or** HTML (`<p>`, `<ol>`). HTML wins if the string contains a tag; otherwise Markdown is converted to HTML.
- A single string `value` is copied to both `en` and `id`. A missing locale is stored empty, not filled from defaults.
- Override repeaters (`add_on`, timelines): JSON arrays. A flat array is copied to both locales.
- Shared repeaters (`video_testimonials`, `client_logos`) are not translatable. Send `[{ "url": "https://..." }]`.
- Optional `template` on a `default` field looks up a different key in content defaults (`ecommerce_features`, `marketing_program`, `short_project_timeline`, …).
- Payload `timeline_template` applies to both offer timeline fields when their mode is `default`.

### Proposal `content` keys (all required)

`brief`, `extra_content_brief`, `core_services`, `features`, `server`, `assets`, `security`, `support`, `additional_benefit`, `add_on`, `payment`, `terms_condition`, `additional_info`, `faq`, `our_process`, `about_us`, `video_testimonials`, `client_logos`, `offer_1_project_timeline`, `offer_2_project_timeline`

### SPK `content` keys (all required)

`title`, `subject`, `content`

`title` has no stored default; `default` leaves it empty. `subject` and `content` `default` copy SPK Content Defaults and resolve placeholders once:

`spk_number`, `spk_date`, `client_company`, `client_pic_name`, `client_pic_role`, `client_address`, `company_name`, `company_pic_name`, `company_pic_role`, `company_address`, `proposal_number`, `proposal_date`, `offer_name`, `offer_price`, `offer_name_1`, `offer_price_1`, `offer_name_2`, `offer_price_2`, `offer_timeline`, `offer_timeline_1`, `offer_timeline_2`, `subject`

### Invoice

No content-default catalog. Standalone create requires `items` and `content.additional_info` with mode `override` or `empty` only.

From-proposal invoices copy Offer 1 into the first item (suffix `DP`, unpaid) unless you send `items`. Optional `offer` `1|2` and `renewal: true`. `content.additional_info` may use `default` to copy the proposal.

## Client

Send `client_id` **or** `client` snapshot:

```json
"client": {
  "company": "PT Contoh",
  "name": "Budi",
  "email": "budi@contoh.test",
  "phone": "08123456789",
  "address": "Jl. Contoh 1"
}
```

No `client_id` → a new Client is created, then linked. Do not assume email uniqueness; pass `client_id` to reuse. Optional `client` fields on an existing `client_id` override snapshot fields on the document only.

## Dry-run

```json
{ "dry_run": true }
```

Does not write. `200` with `valid`, `would_create`, `resolved_content_preview`, `warnings`. Invalid bodies return `422`.

## Create success

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

## Errors

| Status | Meaning |
|---|---|
| 401 | Missing/invalid API key |
| 404 | Unknown id |
| 422 | Validation. Read `errors` and `missing_content_fields`. Fetch the skeleton. Do not retry the same body. |
| 429 | Rate limited. Wait and retry. |
| 503 | Server API user is not configured |

## From a proposal

Invoice: copies frozen client snapshot, builds one item from Offer 1, suffix `DP`, unpaid.

SPK: copies client/company snapshots. Send `company_pic_index` (0-based from `GET /companies/{id}`) or `company_pic_name` + `company_pic_role`. Content modes still required; `default` interpolates placeholders.
