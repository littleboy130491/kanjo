<?php

namespace App\Http\Middleware;

use App\Models\Invoice;
use App\Models\Proposal;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\Response;

class DocumentAuthThrottleMiddleware
{
    public const MAX_ATTEMPTS = 5;

    public const DECAY_SECONDS = 600;

    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $key = self::key($request);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = (int) ceil($seconds / 60);
            $documentType = (string) $request->route()?->defaults['document_type'] ?? 'document';
            $slug = (string) $request->route('slug');
            $subject = self::resolveSubject($documentType, $slug);

            activity('security')
                ->event('rate_limited')
                ->performedOn($subject)
                ->tap(function (Activity $activity) use ($request, $seconds, $minutes): void {
                    $activity->description = 'Document auth rate limited';
                    $activity->ip_address = $request->ip();
                    $activity->device = $request->userAgent();
                })
                ->withProperties([
                    'document_type' => $documentType,
                    'slug' => $slug,
                    'wait_seconds' => $seconds,
                    'wait_minutes' => $minutes,
                    'max_attempts' => self::MAX_ATTEMPTS,
                ])
                ->log('Document auth rate limited');

            return back()
                ->withErrors([
                    'credentials' => "Too many attempts. Please try again in {$minutes} minute" . ($minutes === 1 ? '' : 's') . '.',
                ])
                ->withInput();
        }

        return $next($request);
    }

    public static function key(Request $request): string
    {
        $documentType = (string) $request->route()?->defaults['document_type'] ?? 'document';
        $slug = (string) $request->route('slug');
        $ip = (string) $request->ip();

        return strtolower($documentType . '|' . $slug . '|' . $ip);
    }

    protected static function resolveSubject(string $documentType, string $slug): ?Model
    {
        return match ($documentType) {
            'proposal' => Proposal::query()->where('slug', $slug)->first(),
            'invoice' => Invoice::query()->where('slug', $slug)->first(),
            default => null,
        };
    }
}
