<!doctype html>
<html lang="{{ $lang }}">
@php
    $credentialsError = $errors->first('credentials');
    $isRateLimited = str_contains($credentialsError, 'Too many attempts.');
@endphp

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Document Access</title>
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
                        Please sign in to view this document.
                    </h1>
                    <p
                        class="mt-5 max-w-xl text-[14px] leading-[1.55] text-neutral-600 md:text-[16px] md:leading-normal">
                        Enter the assigned credentials to unlock this published document.
                    </p>
                </div>

                @if ($errors->has('credentials'))
                    <div class="mt-8 border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        {{ $credentialsError }}
                    </div>
                @endif

                @unless ($isRateLimited)
                    <form method="POST" action="{{ $authRoute }}" class="mt-10 max-w-xl space-y-5 pt-8">
                        @csrf
                        @if(($document->activate_translation ?? false) && filled($lang))
                            <input type="hidden" name="lang" value="{{ $lang }}">
                        @endif

                        <div>
                            <label for="username" class="auth-label">Username</label>
                            <input id="username" name="username" type="text" required value="{{ old('username') }}"
                                class="auth-input" placeholder="Enter your username">
                        </div>

                        <div>
                            <label for="password" class="auth-label">Password</label>
                            <input id="password" name="password" type="password" required class="auth-input"
                                placeholder="Enter your password">
                        </div>

                        <button type="submit" class="auth-button">
                            Open Document
                        </button>
                    </form>
                @endunless
            </div>
        </section>
    </main>
</body>

</html>
