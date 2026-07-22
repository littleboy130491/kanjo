<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Proposal;
use App\Models\Spk;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;

class PdfController extends Controller
{
    public function proposal(Request $request, string $slug): Response
    {
        /** @var Proposal|null $proposal */
        $proposal = $request->attributes->get('document');

        if (! $proposal) {
            abort(404);
        }

        $locale = $this->resolveLocale($request, $proposal);
        app()->setLocale($locale);

        $proposal->loadMissing(['company', 'portfolios']);

        $html = view('proposals.show', [
            'proposal' => $proposal,
            'locale' => $locale,
            'slug' => $slug,
            'pdf' => true,
        ])->render();

        $pdf = Browsershot::html($html)
            ->noSandbox()
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->pdf();

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->buildFilename(
                $proposal->client_company,
                $proposal->document_number,
            ).'"',
        ]);
    }

    public function invoice(Request $request, string $slug): Response
    {
        /** @var Invoice|null $invoice */
        $invoice = $request->attributes->get('document');

        if (! $invoice) {
            abort(404);
        }

        $locale = $this->resolveLocale($request, $invoice);
        app()->setLocale($locale);

        $invoice->loadMissing(['company', 'proposal']);

        $html = view('invoices.show', [
            'invoice' => $invoice,
            'locale' => $locale,
            'slug' => $slug,
            'pdf' => true,
        ])->render();

        $pdf = Browsershot::html($html)
            ->noSandbox()
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->pdf();

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->buildFilename(
                $invoice->client_company,
                $invoice->document_number,
            ).'"',
        ]);
    }

    public function spk(Request $request, string $slug): Response
    {
        /** @var Spk|null $spk */
        $spk = $request->attributes->get('document');

        if (! $spk) {
            abort(404);
        }

        $locale = $this->resolveLocale($request, $spk);
        app()->setLocale($locale);

        $spk->loadMissing(['company', 'proposal']);

        $html = view('spks.show', [
            'spk' => $spk,
            'locale' => $locale,
            'slug' => $slug,
            'pdf' => true,
        ])->render();

        $pdf = Browsershot::html($html)
            ->noSandbox()
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->pdf();

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->buildFilename(
                $spk->client_company,
                $spk->document_number,
            ).'"',
        ]);
    }

    private function buildFilename(?string $clientCompany, string $documentNumber): string
    {
        $company = Str::of($clientCompany ?: 'client')
            ->replaceMatches('/[^\pL\pN]+/u', '-')
            ->trim('-')
            ->upper();

        $number = Str::of($documentNumber)
            ->replace('/', '-')
            ->replaceMatches('/[^\pL\pN-]+/u', '-')
            ->trim('-');

        return "{$company}-{$number}.pdf";
    }

    private function resolveLocale(Request $request, Proposal|Invoice|Spk|null $document = null): string
    {
        if ($document instanceof Spk) {
            $supported = config('app.supported_locales', ['en', 'id']);
            $locale = (string) $request->query('lang', config('app.locale', 'en'));

            return in_array($locale, $supported, true) ? $locale : config('app.locale', 'en');
        }

        if (! ($document?->activate_translation)) {
            return config('app.locale', 'en');
        }

        $supported = config('app.supported_locales', ['en', 'id']);
        $locale = (string) $request->query('lang', config('app.locale', 'en'));

        return in_array($locale, $supported, true) ? $locale : config('app.locale', 'en');
    }
}
