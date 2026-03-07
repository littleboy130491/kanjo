<?php

namespace App\Http\Controllers;

use App\Http\Middleware\DocumentAccessMiddleware;
use App\Models\Proposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProposalViewController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        /** @var Proposal|null $proposal */
        $proposal = $request->attributes->get('document');

        if (! $proposal) {
            abort(404);
        }

        $proposal->loadMissing(['company', 'portfolios']);

        return view('proposals.show', [
            'proposal' => $proposal,
            'locale' => $locale,
            'slug' => $slug,
        ]);
    }

    public function authenticate(Request $request, string $slug): RedirectResponse
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
            return back()->withErrors([
                'credentials' => 'Invalid credentials.',
            ])->withInput();
        }

        $request->session()->put([
            DocumentAccessMiddleware::sessionKey('proposal', $proposal->id) => true,
            DocumentAccessMiddleware::versionKey('proposal', $proposal->id) => DocumentAccessMiddleware::credentialVersion($proposal),
        ]);

        return redirect()->route('proposal.show', [
            'slug' => $slug,
            'lang' => $request->input('lang'),
        ]);
    }

    private function resolveLocale(Request $request): string
    {
        $supported = config('app.supported_locales', ['en', 'id']);
        $locale = (string) $request->query('lang', config('app.locale', 'en'));

        return in_array($locale, $supported, true) ? $locale : config('app.locale', 'en');
    }
}
