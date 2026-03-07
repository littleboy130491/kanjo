<?php

namespace App\Http\Middleware;

use App\Enums\DocumentStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Proposal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DocumentAccessMiddleware
{
    public function handle(Request $request, Closure $next, string $type): Response
    {
        $slug = (string) $request->route('slug');
        $document = $this->resolveDocument($type, $slug);

        if (! $document || $document->status !== DocumentStatus::PUBLISHED) {
            abort(404);
        }

        if ($this->canBypassDocumentAccess()) {
            $request->attributes->set('document', $document);

            return $next($request);
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

            return response()->view('auth.document-access', [
                'document' => $document,
                'documentType' => $type,
                'slug' => $slug,
                'authRoute' => route($type.'.auth', ['slug' => $slug]),
                'lang' => $request->query('lang', config('app.locale', 'en')),
                'usesDocumentCredentials' => $useDocumentCredentials,
            ]);
        }

        $request->attributes->set('document', $document);

        return $next($request);
    }

    public static function sessionKey(string $type, int $id): string
    {
        return "doc_auth_{$type}_{$id}";
    }

    public static function versionKey(string $type, int $id): string
    {
        return "doc_auth_{$type}_{$id}_version";
    }

    public static function credentialVersion(Proposal|Invoice $document): string
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

    private function resolveDocument(string $type, string $slug): Proposal|Invoice|null
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
            default => null,
        };
    }

    private function resolveCredentials(Proposal|Invoice $document): array
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
