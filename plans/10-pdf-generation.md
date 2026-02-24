# Activity 10 — PDF Generation (Browsershot)

## Goal
Generate branded PDFs for proposals and invoices using Browsershot (headless Chrome via Puppeteer).

---

## Package
```
spatie/browsershot
```
Also requires Node.js and Puppeteer:
```bash
npm install puppeteer
```

---

## Flow

1. Admin or client clicks "Download PDF"
2. Route calls `PdfController@proposal` or `PdfController@invoice`
3. Controller renders the same Blade view used for client display, but with `?pdf=true` flag
4. Browsershot captures the rendered HTML and returns a PDF
5. Response is a file download

---

## Controller

**File:** `app/Http/Controllers/PdfController.php`

```php
public function proposal(string $slug): Response
{
    $proposal = Proposal::where('slug', $slug)->firstOrFail();
    // Access check (same middleware as client view)

    $html = view('proposals.show', ['proposal' => $proposal, 'pdf' => true])->render();

    $pdf = Browsershot::html($html)
        ->format('A4')
        ->margins(15, 15, 15, 15)
        ->showBackground()
        ->pdf();

    return response($pdf)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'attachment; filename="' . $proposal->document_number . '.pdf"');
}
```

Same pattern for `invoice`.

---

## Routes

```php
Route::get('/proposal/{slug}/pdf', [PdfController::class, 'proposal']);
Route::get('/invoice/{slug}/pdf', [PdfController::class, 'invoice']);
```

Both routes should also go through the `DocumentAccessMiddleware`.

---

## Blade Template Adjustments

Add PDF-mode conditional to hide interactive UI elements:
```blade
@if(!($pdf ?? false))
  <a href="...">Download PDF</a>
  <div class="lang-switcher">EN / ID</div>
@endif
```

---

## Print CSS for PDF
Critical rules to include via `@media print` or inline when `$pdf = true`:

```css
@page {
  size: A4;
  margin: 20mm;
}

/* Repeat header on each page */
thead { display: table-header-group; }
tfoot { display: table-footer-group; }

/* Prevent awkward breaks */
.section { page-break-inside: avoid; }
h2, h3 { page-break-after: avoid; }

/* Company letterhead on every page */
.pdf-header {
  position: fixed;
  top: 0;
  width: 100%;
}
.pdf-footer {
  position: fixed;
  bottom: 0;
  width: 100%;
}
```

---

## Filament Action Integration

In `ProposalResource` and `InvoiceResource`, add an action:
```php
Action::make('pdf')
    ->label('Download PDF')
    ->url(fn ($record) => route('pdf.proposal', $record->slug))
    ->openUrlInNewTab()
```

---

## Server Requirements
- Node.js installed (Laravel Sail includes this)
- Puppeteer npm package installed
- Chrome/Chromium available (Browsershot can use bundled Puppeteer Chromium)
- Sufficient memory for headless Chrome (minimum 512MB)

---

## Acceptance Criteria
- PDF download produces a valid, multi-page PDF for a complex proposal
- Company logo renders in PDF header
- Brand colors applied correctly in PDF
- Document number appears in filename
- Language is respected (PDF rendered in selected locale)
- Print CSS hides nav and PDF button from output
