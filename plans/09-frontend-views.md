# Activity 09 — Frontend Views (Client-Facing)

## Goal
Build the HTML views clients see when accessing a proposal or invoice URL. Views must be branded per company and support bilingual rendering.

---

## Tech
- Blade templates
- Tailwind CSS
- Print-optimized CSS for PDF compatibility

---

## Proposal View

**File:** `resources/views/proposals/show.blade.php`

### Sections (in order)
1. **Header** — Company logo, company name, document number, issue date, valid until
2. **Client Info** — Client company, client name, client email
3. **Brief** — Translated content
4. **Portfolios** — Grid of portfolio items with image + link
5. **Core Services** — Translated list
6. **Features** — Translated feature name + description pairs
7. **Server** — Translated list
8. **Assets** — Translated list
9. **Security** — Translated list
10. **Support** — Translated list
11. **Offer 1** — `offer_name_1`, price (with strikethrough original if discounted), renewal price, project timeline table
12. **Offer 2** — Same structure, hidden if `offer_name_2` is null
13. **Additional Benefit** — Translated list
14. **Add-Ons** — Name, description, price per item
15. **Payment Terms** — Payment info, down payment amount
16. **Terms & Conditions** — Title + description pairs
17. **Tax Summary** — Subtotal, tax rate, tax amount, total
18. **Footer** — Company footer text (translated), bank details, PIC signatures

---

## Invoice View

**File:** `resources/views/invoices/show.blade.php`

### Sections (in order)
1. **Header** — Company logo, document number, issue date, due date, payment status badge
2. **Client Info** — Frozen client fields
3. **Items Table** — Title, description, price per item
4. **Financial Summary** — Subtotal, tax rate, tax amount, total
5. **Payment Info** — `paid_amount`, `payment_method`, `paid_at` (shown if partially/fully paid)
6. **Footer** — Bank details, PIC, company footer text

---

## Branding
All views use company colors from `color_primary` and `color_secondary` as CSS custom properties:
```html
<style>
  :root {
    --color-primary: {{ $company->color_primary }};
    --color-secondary: {{ $company->color_secondary }};
  }
</style>
```

Logo from Curator media:
```html
<img src="{{ $company->logo_url }}" alt="{{ $company->brand_name }}">
```

---

## Language Switcher
A simple toggle (EN / ID) that sets the locale and re-renders the page:
```php
Route::get('/proposal/{slug}/{locale}', [ProposalController::class, 'setLocale']);
```
Or use a query param `?lang=id`.

---

## PDF Button
Visible to both admin (via Filament action) and client:
```html
<a href="/proposal/{{ $slug }}/pdf">Download PDF</a>
```

---

## Print / PDF CSS

**File:** `resources/css/print.css` (or inline in blade)

Key rules:
- `@page { margin: 20mm; }`
- Hide language switcher, nav, PDF button when printing
- Ensure page breaks don't split sections awkwardly
- Company letterhead visible at the top of every page using `position: fixed` header

---

## Acceptance Criteria
- Proposal view renders all sections correctly in both EN and ID
- Invoice view renders items table and financial summary
- Company logo and brand colors applied correctly
- Print/PDF CSS hides UI chrome and renders cleanly
- Language switcher toggles translated content without re-authenticating
