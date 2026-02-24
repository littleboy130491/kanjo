# Activity 08 — Client Access (Frontend Authentication)

## Goal
Implement per-document, session-based authentication for clients viewing proposals and invoices. No user accounts — just credentials per document.

---

## Access Flow

1. Client visits `/proposal/{slug}` or `/invoice/{slug}`
2. Middleware checks if the document requires authentication:
   - If `access_username` + `access_password` are set on the document → use document-level credentials
   - Else → fall back to `.env` globals: `GLOBAL_ACCESS_USERNAME`, `GLOBAL_ACCESS_PASSWORD`
3. If not yet authenticated for this document, redirect to a login form
4. On successful login, store auth in session keyed by document type + ID
5. Client stays authenticated for the duration of the browser session

---

## Routes

**File:** `routes/web.php`

```php
Route::get('/proposal/{slug}', [ProposalController::class, 'show']);
Route::post('/proposal/{slug}/auth', [ProposalController::class, 'authenticate']);

Route::get('/invoice/{slug}', [InvoiceController::class, 'show']);
Route::post('/invoice/{slug}/auth', [InvoiceController::class, 'authenticate']);
```

**Slug:** Use `document_number` (URL-encoded or with slashes replaced) or a dedicated `slug` column. Recommend replacing `/` with `-` in the URL slug, e.g., `QUO-001-IV-26-NEW`.

---

## Middleware: `DocumentAccessMiddleware`

**File:** `app/Http/Middleware/DocumentAccessMiddleware.php`

**Logic:**
```php
// 1. Load document
// 2. Determine credentials to use (document-level or .env global)
// 3. Check session key: "doc_auth_{type}_{id}"
// 4. If not authenticated → return login view
// 5. If authenticated → continue
```

Apply via route middleware or inline in controller.

---

## Controllers

### `ProposalController`

**`show(string $slug)`**
- Find proposal by slug
- Check `status = 'published'` (else 404)
- Apply access check
- Return view `proposals.show` with proposal data at current locale

**`authenticate(Request $request, string $slug)`**
- Validate `username` + `password` inputs
- Compare against document credentials (or global fallback)
- On success: store `session("doc_auth_proposal_{$proposal->id}", true)`
- Redirect back to proposal view

### `InvoiceController`
Same pattern as ProposalController for `/invoice/{slug}`.

---

## Login View

**File:** `resources/views/auth/document-login.blade.php`

Simple centered form:
- Document number shown at top
- Username + password inputs
- Submit button
- Error message on failure

---

## Password Handling
- `access_password` stored as a **hashed** value in the database
- Use `Hash::check($input, $document->access_password)` for comparison
- Global `.env` password compared with `hash_equals()` or `Hash::check()` depending on storage format

---

## Session Key Convention
```
doc_auth_proposal_{id}
doc_auth_invoice_{id}
```
This ensures authentication is per-document, not shared across documents.

---

## Acceptance Criteria
- A published proposal with credentials requires login before viewing
- Correct credentials grant access; incorrect credentials show an error
- A published proposal with no credentials uses the `.env` fallback
- A draft document returns 404 (not accessible to clients)
- Session persists across page refreshes within the same browser session
- Accessing a different document requires separate authentication
