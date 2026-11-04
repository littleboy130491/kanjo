<?php

namespace App\Http\Middleware;

use App\Enums\DocumentStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Proposal;
use App\Models\Spk;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DocumentAccessMiddleware
{
    public function handle(Request $request, Closure $next, string $type, ?string $authRoute = null): Response
    {
        $slug = (string) $request->route('slug');
        $document = $this->resolveDocument($type, $slug);

        if (! $document) {
            abort(404);
        }

        if ($this->canBypassDocumentAccess()) {
            $request->attributes->set('document', $document);

            return $next($request);
        }

        if ($document instanceof Proposal && $document->status === DocumentStatus::EXPIRED) {
            abort_if($request->expectsJson(), 404);

            return $this->expiredResponse($request, $document);
        }

        if ($document->status !== DocumentStatus::PUBLISHED) {
            abort(404);
        }

        [$username, $password, $useDocumentCredentials] = $this->resolveCredentials($document);

        if ($username === null || $password === null) {
            abort(403, 'Document access credentials are not configured.');
        }

        $sessionKey = $this->sessionKey($type, $document->id);
        $versionKey = $this->versionKey($type, $document->id);
        $expectedVersion = self::credentialVersion($document);

        if (
            ! $request->session()->get($sessionKey, false)
            || $request->session()->get($versionKey) !== $expectedVersion
        ) {
            $request->session()->forget([$sessionKey, $versionKey]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Document access required.'], 401);
            }

            return response()->view('auth.document-access', [
                'document' => $document,
                'documentType' => $type,
                'slug' => $slug,
                'authRoute' => route($authRoute ?? $type.'.auth', ['slug' => $slug]),
                'lang' => $document->activate_translation
                    ? $request->query('lang', config('app.locale', 'en'))
                    : config('app.locale', 'en'),
                'usesDocumentCredentials' => $useDocumentCredentials,
            ]);
        }

        $request->attributes->set('document', $document);

        return $next($request);
    }

    private function expiredResponse(Request $request, Proposal $proposal): Response
    {
        $proposal->loadMissing('company');
        $lang = $proposal->activate_translation
            ? (string) $request->query('lang', config('app.locale', 'en'))
            : (string) config('app.locale', 'en');

        return response()->view('proposals.expired', [
            'proposal' => $proposal,
            'company' => $proposal->company,
            'lang' => in_array($lang, config('app.supported_locales', ['en', 'id']), true) ? $lang : config('app.locale', 'en'),
        ], 410);
    }

    public static function sessionKey(string $type, int $id): string
    {
        return "doc_auth_{$type}_{$id}";
    }

    public static function versionKey(string $type, int $id): string
    {
        return "doc_auth_{$type}_{$id}_version";
    }

    public static function credentialVersion(Proposal|Invoice|Spk $document): string
    {
        return $document->access_credentials_updated_at?->toIso8601String() ?? 'initial';
    }

    public static function passwordsMatch(string $input, string $expected): bool
    {
        return hash_equals($expected, $input);
    }

    private function canBypassDocumentAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasAnyRole([
            UserRole::SuperAdmin->value,
            UserRole::Editor->value,
        ]);
    }

    private function resolveDocument(string $type, string $slug): Proposal|Invoice|Spk|null
    {
        $documentNumber = str_replace('-', '/', $slug);

        return match ($type) {
            'proposal' => Proposal::query()
                ->where(fn ($query) => $query
                    ->where('slug', $slug)
                    ->orWhere('document_number', $documentNumber))
                ->first(),
            'invoice' => Invoice::query()
                ->where(fn ($query) => $query
                    ->where('slug', $slug)
                    ->orWhere('document_number', $documentNumber))
                ->first(),
            'spk' => Spk::query()
                ->where(fn ($query) => $query
                    ->where('slug', $slug)
                    ->orWhere('document_number', $documentNumber))
                ->first(),
            default => null,
        };
    }

    private function resolveCredentials(Proposal|Invoice|Spk $document): array
    {
        if (filled($document->access_username) && filled($document->access_password)) {
            return [$document->access_username, $document->access_password, true];
        }

        return [
            config('app.global_access_username'),
            config('app.global_access_password'),
            false,
        ];
    }
}
