<?php

namespace App\Http\Controllers;

use App\Http\Middleware\DocumentAccessMiddleware;
use App\Http\Middleware\DocumentAuthThrottleMiddleware;
use App\Models\Proposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ProposalViewController extends Controller
{
    public function show(Request $request, string $slug)
    {
        return $this->renderProposal($request, $slug, 'proposals.show');
    }

    public function showV2(Request $request, string $slug)
    {
        return $this->renderProposal($request, $slug, 'proposals.show-v2');
    }

    private function renderProposal(Request $request, string $slug, string $view)
    {
        /** @var Proposal|null $proposal */
        $proposal = $request->attributes->get('document');

        if (! $proposal) {
            abort(404);
        }

        $locale = $this->resolveLocale($request, $proposal);
        app()->setLocale($locale);

        $proposal->loadMissing(['company', 'portfolios']);

        return view($view, [
            'proposal' => $proposal,
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
        return $this->redirectToProposal($request, $slug, 'proposal.show');
    }

    public function authenticateRedirectV2(Request $request, string $slug): RedirectResponse
    {
        return $this->redirectToProposal($request, $slug, 'proposal-v2.show');
    }

    private function redirectToProposal(Request $request, string $slug, string $route): RedirectResponse
    {
        return redirect()->route($route, $this->buildRouteParameters(
            $slug,
            $this->resolveDocument($slug),
            $request->query('lang'),
        ));
    }

    public function authenticate(Request $request, string $slug): RedirectResponse
    {
        return $this->authenticateForRoute($request, $slug, 'proposal.show');
    }

    public function authenticateV2(Request $request, string $slug): RedirectResponse
    {
        return $this->authenticateForRoute($request, $slug, 'proposal-v2.show');
    }

    private function authenticateForRoute(Request $request, string $slug, string $route): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $documentNumber = str_replace('-', '/', $slug);

        $proposal = Proposal::query()
            ->where(fn ($query) => $query
                ->where('slug', $slug)
                ->orWhere('document_number', $documentNumber))
            ->first();

        if (! $proposal || $proposal->status->value !== 'published') {
            abort(404);
        }

        $isDocumentCredential = filled($proposal->access_username) && filled($proposal->access_password);
        $expectedUsername = $isDocumentCredential
            ? $proposal->access_username
            : config('app.global_access_username');

        $expectedPassword = $isDocumentCredential
            ? $proposal->access_password
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
            DocumentAccessMiddleware::sessionKey('proposal', $proposal->id) => true,
            DocumentAccessMiddleware::versionKey('proposal', $proposal->id) => DocumentAccessMiddleware::credentialVersion($proposal),
        ]);

        return redirect()->route($route, $this->buildRouteParameters(
            $slug,
            $proposal,
            $request->input('lang'),
        ));
    }

    private function resolveLocale(Request $request, ?Proposal $proposal = null): string
    {
        if (! ($proposal?->activate_translation)) {
            return config('app.locale', 'en');
        }

        $supported = config('app.supported_locales', ['en', 'id']);
        $locale = (string) $request->query('lang', config('app.locale', 'en'));

        return in_array($locale, $supported, true) ? $locale : config('app.locale', 'en');
    }

    private function resolveDocument(string $slug): ?Proposal
    {
        $documentNumber = str_replace('-', '/', $slug);

        return Proposal::query()
            ->where(fn ($query) => $query
                ->where('slug', $slug)
                ->orWhere('document_number', $documentNumber))
            ->first();
    }

    /**
     * @return array<string, string>
     */
    private function buildRouteParameters(string $slug, ?Proposal $proposal, mixed $lang): array
    {
        $parameters = ['slug' => $slug];

        if ($proposal?->activate_translation && is_string($lang) && $lang !== '') {
            $parameters['lang'] = $lang;
        }

        return $parameters;
    }
}
