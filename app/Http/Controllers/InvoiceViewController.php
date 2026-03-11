<?php

namespace App\Http\Controllers;

use App\Http\Middleware\DocumentAccessMiddleware;
use App\Http\Middleware\DocumentAuthThrottleMiddleware;
use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class InvoiceViewController extends Controller
{
    public function show(Request $request, string $slug)
    {
        /** @var Invoice|null $invoice */
        $invoice = $request->attributes->get('document');

        if (! $invoice) {
            abort(404);
        }

        $locale = $this->resolveLocale($request, $invoice);
        app()->setLocale($locale);

        $invoice->loadMissing(['company']);

        return view('invoices.show', [
            'invoice' => $invoice,
            'locale' => $locale,
            'slug' => $slug,
        ]);
    }

    /**
     * Handle GET requests to auth route - redirect to document show page.
     * The middleware will handle showing auth form if needed.
     */
    public function authenticateRedirect(Request $request, string $slug): RedirectResponse
    {
        return redirect()->route('invoice.show', $this->buildRouteParameters(
            $slug,
            $this->resolveDocument($slug),
            $request->query('lang'),
        ));
    }

    public function authenticate(Request $request, string $slug): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $documentNumber = str_replace('-', '/', $slug);

        $invoice = Invoice::query()
            ->where(fn ($query) => $query
                ->where('slug', $slug)
                ->orWhere('document_number', $documentNumber))
            ->first();

        if (! $invoice || $invoice->status->value !== 'published') {
            abort(404);
        }

        $isDocumentCredential = filled($invoice->access_username) && filled($invoice->access_password);
        $expectedUsername = $isDocumentCredential
            ? $invoice->access_username
            : config('app.global_access_username');

        $expectedPassword = $isDocumentCredential
            ? $invoice->access_password
            : config('app.global_access_password');

        if (! $expectedUsername || ! $expectedPassword) {
            abort(403, 'Document access credentials are not configured.');
        }

        $usernameMatches = hash_equals($expectedUsername, (string) $request->string('username'));
        $passwordMatches = DocumentAccessMiddleware::passwordsMatch(
            (string) $request->string('password'),
            $expectedPassword,
        );

        if (! $usernameMatches || ! $passwordMatches) {
            RateLimiter::hit(
                DocumentAuthThrottleMiddleware::key($request),
                DocumentAuthThrottleMiddleware::DECAY_SECONDS,
            );

            return back()->withErrors([
                'credentials' => 'Invalid credentials.',
            ])->withInput();
        }

        RateLimiter::clear(DocumentAuthThrottleMiddleware::key($request));

        $request->session()->put([
            DocumentAccessMiddleware::sessionKey('invoice', $invoice->id) => true,
            DocumentAccessMiddleware::versionKey('invoice', $invoice->id) => DocumentAccessMiddleware::credentialVersion($invoice),
        ]);

        return redirect()->route('invoice.show', $this->buildRouteParameters(
            $slug,
            $invoice,
            $request->input('lang'),
        ));
    }

    private function resolveLocale(Request $request, ?Invoice $invoice = null): string
    {
        if (! ($invoice?->activate_translation)) {
            return config('app.locale', 'en');
        }

        $supported = config('app.supported_locales', ['en', 'id']);
        $locale = (string) $request->query('lang', config('app.locale', 'en'));

        return in_array($locale, $supported, true) ? $locale : config('app.locale', 'en');
    }

    private function resolveDocument(string $slug): ?Invoice
    {
        $documentNumber = str_replace('-', '/', $slug);

        return Invoice::query()
            ->where(fn ($query) => $query
                ->where('slug', $slug)
                ->orWhere('document_number', $documentNumber))
            ->first();
    }

    /**
     * @return array<string, string>
     */
    private function buildRouteParameters(string $slug, ?Invoice $invoice, mixed $lang): array
    {
        $parameters = ['slug' => $slug];

        if ($invoice?->activate_translation && is_string($lang) && $lang !== '') {
            $parameters['lang'] = $lang;
        }

        return $parameters;
    }
}
