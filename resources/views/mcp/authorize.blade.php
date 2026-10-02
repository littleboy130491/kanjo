<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    $canConnect = $user->canAccessPanel(\Filament\Facades\Filament::getPanel('admin'));
@endphp

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connect AI Assistant - {{ config('app.name') }}</title>
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="googlebot" content="noindex, nofollow, noarchive">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Montserrat:wght@200;300;400;500;600&display=swap"
        rel="stylesheet">
    @vite('resources/css/app.css')
</head>

<body class="auth-shell">
    <main class="mx-auto flex min-h-[calc(100vh-3rem)] max-w-[210mm] items-center">
        <section class="auth-panel">
            <div class="auth-inner">
                <div>
                    <h1 class="auth-title">
                        Connect {{ $client->name }} to {{ config('app.name') }}?
                    </h1>
                    <p
                        class="mt-5 max-w-xl text-[14px] leading-[1.55] text-neutral-600 md:text-[16px] md:leading-normal">
                        This assistant will be able to look up companies, clients, and services, and create or
                        update proposals, invoices, and SPKs as you. Documents it creates are published
                        immediately.
                    </p>
                </div>

                <div class="mt-10 max-w-xl pt-8">
                    <span class="auth-label">Signed in as</span>
                    <p class="text-sm text-neutral-800">{{ $user->name }} &middot; {{ $user->email }}</p>
                </div>

                @if ($client->client_uri ?? null)
                    <div class="mt-6 max-w-xl">
                        <span class="auth-label">Application</span>
                        <a href="{{ $client->client_uri }}" target="_blank" rel="noopener noreferrer"
                            class="text-sm text-neutral-800 underline">{{ $client->client_uri }}</a>
                    </div>
                @endif

                @unless ($canConnect)
                    <div class="mt-8 max-w-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        Your account does not have admin panel access, so it cannot connect an AI assistant.
                    </div>
                @endunless

                <div class="mt-10 flex max-w-xl flex-col gap-3 sm:flex-row-reverse">
                    @if ($canConnect)
                        <form method="POST" action="{{ route('passport.authorizations.approve') }}" class="flex-1">
                            @csrf
                            <input type="hidden" name="state" value="">
                            <input type="hidden" name="client_id" value="{{ $client->id }}">
                            <input type="hidden" name="auth_token" value="{{ $authToken }}">
                            <button type="submit" class="auth-button">Authorize</button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('passport.authorizations.deny') }}" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="state" value="">
                        <input type="hidden" name="client_id" value="{{ $client->id }}">
                        <input type="hidden" name="auth_token" value="{{ $authToken }}">
                        <button type="submit"
                            class="inline-flex w-full items-center justify-center border border-slate-300 px-4 py-3 text-sm font-medium uppercase tracking-[0.2em] text-slate-700 transition hover:bg-slate-50 focus:outline-none">
                            Cancel
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </main>
</body>

</html>
