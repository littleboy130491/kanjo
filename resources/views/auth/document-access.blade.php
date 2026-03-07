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
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Montserrat', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        serif: ['Cormorant Garamond', 'ui-serif', 'Georgia', 'serif'],
                    },
                    boxShadow: {
                        paper: '0 30px 60px -15px rgba(0, 0, 0, 0.1)',
                    },
                },
            },
        };
    </script>
    <style type="text/tailwindcss">
        @layer components {
            .auth-shell {
                @apply min-h-screen bg-slate-100 px-4 py-6 font-sans text-neutral-800 md:px-8 md:py-8;
            }

            .auth-panel {
                @apply mx-auto w-full max-w-[1000px] bg-white shadow-paper;
            }

            .auth-inner {
                @apply mx-auto max-w-2xl px-6 py-12 md:px-24 md:py-16;
            }


            .auth-title {
                @apply font-serif text-3xl leading-[0.95] text-neutral-900 md:text-5xl;
            }

            .auth-label {
                @apply mb-2 block text-[10px] font-semibold uppercase tracking-[0.25em] text-slate-500;
            }

            .auth-input {
                @apply w-full border-0 border-b border-sky-100 bg-transparent px-0 py-3 text-sm text-neutral-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-0;
            }

            .auth-button {
                @apply inline-flex w-full items-center justify-center bg-slate-900 px-4 py-3 text-sm font-medium uppercase tracking-[0.2em] text-white transition hover:bg-slate-800 focus:outline-none;
            }
        }
    </style>
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
                        <input type="hidden" name="lang" value="{{ $lang }}">

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
