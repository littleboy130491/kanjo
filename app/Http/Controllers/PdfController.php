<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\Browsershot\Browsershot;

class PdfController extends Controller
{
    public function proposal(Request $request, string $slug): Response
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        /** @var Proposal|null $proposal */
        $proposal = $request->attributes->get('document');

        if (! $proposal) {
            abort(404);
        }

        $proposal->loadMissing(['company', 'portfolios']);

        $html = view('proposals.show', [
            'proposal' => $proposal,
            'locale' => $locale,
            'slug' => $slug,
            'pdf' => true,
        ])->render();

        $pdf = Browsershot::html($html)
            ->format('A4')
            ->margins(15, 15, 15, 15)
            ->showBackground()
            ->pdf();

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.str_replace('/', '-', $proposal->document_number).'.pdf"',
        ]);
    }

    public function invoice(Request $request, string $slug): Response
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        /** @var Invoice|null $invoice */
        $invoice = $request->attributes->get('document');

        if (! $invoice) {
            abort(404);
        }

        $invoice->loadMissing(['company']);

        $html = view('invoices.show', [
            'invoice' => $invoice,
            'locale' => $locale,
            'slug' => $slug,
            'pdf' => true,
        ])->render();

        $pdf = Browsershot::html($html)
            ->format('A4')
            ->margins(15, 15, 15, 15)
            ->showBackground()
            ->pdf();

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.str_replace('/', '-', $invoice->document_number).'.pdf"',
        ]);
    }

    private function resolveLocale(Request $request): string
    {
        $supported = config('app.supported_locales', ['en', 'id']);
        $locale = (string) $request->query('lang', config('app.locale', 'en'));

        return in_array($locale, $supported, true) ? $locale : config('app.locale', 'en');
    }
}
