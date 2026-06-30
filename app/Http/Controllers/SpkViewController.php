<?php

namespace App\Http\Controllers;

use App\Http\Middleware\DocumentAccessMiddleware;
use App\Http\Middleware\DocumentAuthThrottleMiddleware;
use App\Models\Spk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class SpkViewController extends Controller
{
    public function show(Request $request, string $slug)
    {
        /** @var Spk|null $spk */
        $spk = $request->attributes->get('document');

        if (! $spk) {
            abort(404);
        }

        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        $spk->loadMissing(['company', 'proposal']);

        return view('spks.show', [
            'spk' => $spk,
            'locale' => $locale,
            'slug' => $slug,
        ]);
    }

    public function authenticateRedirect(Request $request, string $slug): RedirectResponse
    {
        return redirect()->route('spk.show', $this->buildRouteParameters($slug, $request->query('lang')));
    }

    public function authenticate(Request $request, string $slug): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $documentNumber = str_replace('-', '/', $slug);

        $spk = Spk::query()
            ->where(fn ($query) => $query
                ->where('slug', $slug)
                ->orWhere('document_number', $documentNumber))
            ->first();

        if (! $spk || $spk->status->value !== 'published') {
            abort(404);
        }

        $isDocumentCredential = filled($spk->access_username) && filled($spk->access_password);
        $expectedUsername = $isDocumentCredential
            ? $spk->access_username
            : config('app.global_access_username');

        $expectedPassword = $isDocumentCredential
            ? $spk->access_password
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
            DocumentAccessMiddleware::sessionKey('spk', $spk->id) => true,
            DocumentAccessMiddleware::versionKey('spk', $spk->id) => DocumentAccessMiddleware::credentialVersion($spk),
        ]);

        return redirect()->route('spk.show', $this->buildRouteParameters($slug, $request->input('lang')));
    }

    private function resolveLocale(Request $request): string
    {
        $supported = config('app.supported_locales', ['en', 'id']);
        $locale = (string) $request->query('lang', config('app.locale', 'en'));

        return in_array($locale, $supported, true) ? $locale : config('app.locale', 'en');
    }

    /**
     * @return array<string, string>
     */
    private function buildRouteParameters(string $slug, mixed $lang): array
    {
        $parameters = ['slug' => $slug];

        if (is_string($lang) && $lang !== '') {
            $parameters['lang'] = $lang;
        }

        return $parameters;
    }
}
