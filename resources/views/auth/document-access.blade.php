<!doctype html>
<html lang="{{ $lang }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Document Access</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Space Grotesk', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    boxShadow: {
                        gate: '0 24px 80px rgba(15, 23, 42, 0.18)',
                    },
                },
            },
        };
    </script>
    <style type="text/tailwindcss">
        @layer components {
            .auth-shell {
                @apply relative min-h-screen overflow-hidden bg-slate-950 text-slate-100;
            }

            .auth-orb {
                @apply absolute rounded-full blur-3xl;
            }

            .auth-panel {
                @apply relative w-full max-w-5xl overflow-hidden rounded-[2rem] border border-white/10 bg-white/95 shadow-gate backdrop-blur;
            }

            .auth-panel-side {
                @apply relative hidden min-h-[640px] overflow-hidden bg-slate-900 lg:flex;
            }

            .auth-label {
                @apply mb-2 block text-xs font-semibold uppercase tracking-[0.24em] text-slate-500;
            }

            .auth-input {
                @apply w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10;
            }

            .auth-button {
                @apply inline-flex w-full items-center justify-center rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-900/20;
            }

            .auth-chip {
                @apply inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.2em] text-white/80;
            }
        }
    </style>
</head>
<body class="auth-shell">
<div class="auth-orb -left-24 top-10 h-72 w-72 bg-cyan-400/20"></div>
<div class="auth-orb right-0 top-1/3 h-80 w-80 bg-emerald-400/15"></div>
<div class="auth-orb bottom-0 left-1/3 h-64 w-64 bg-amber-300/10"></div>

<main class="relative mx-auto flex min-h-screen max-w-7xl items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
    <div class="auth-panel grid lg:grid-cols-[1.1fr_0.9fr]">
        <section class="auth-panel-side">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(34,211,238,0.24),_transparent_34%),linear-gradient(160deg,_rgba(15,23,42,0.96),_rgba(15,23,42,0.88))]"></div>
            <div class="absolute inset-x-0 bottom-0 h-48 bg-[linear-gradient(180deg,_transparent,_rgba(15,23,42,0.8))]"></div>

            <div class="relative flex w-full flex-col justify-between p-10 xl:p-12">
                <div class="space-y-6">
                    <span class="auth-chip">Protected {{ ucfirst($documentType) }}</span>
                    <div class="max-w-md space-y-4">
                        <p class="text-sm font-medium uppercase tracking-[0.28em] text-cyan-200/80">Agency Document Portal</p>
                        <h1 class="text-4xl font-semibold leading-tight text-white xl:text-5xl">
                            Secure access for {{ $document->client_company ?: $document->client_name }}.
                        </h1>
                        <p class="max-w-sm text-sm leading-7 text-slate-300">
                            Use the credentials assigned to this document to review the published file and its commercial details.
                        </p>
                    </div>
                </div>

                <div class="grid gap-4">
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur-sm">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Document Number</p>
                        <p class="mt-2 text-lg font-semibold text-white">{{ $document->document_number }}</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur-sm">
                            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Language</p>
                            <p class="mt-2 text-sm font-medium text-white">{{ strtoupper($lang) }}</p>
                        </div>
                        <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur-sm">
                            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Credential Source</p>
                            <p class="mt-2 text-sm font-medium text-white">{{ $usesDocumentCredentials ? 'Document-specific' : 'Global access' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="relative bg-white px-6 py-8 sm:px-8 sm:py-10 lg:px-10 lg:py-12">
            <div class="mx-auto flex min-h-full max-w-md flex-col justify-center">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">Document Access</p>
                    <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">
                        Sign in to continue
                    </h2>
                    <p class="mt-3 text-sm leading-7 text-slate-600">
                        Enter the username and password for this {{ $documentType }} to unlock the document view.
                    </p>
                </div>

                @if ($errors->has('credentials'))
                    <div class="mt-6 rounded-3xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        {{ $errors->first('credentials') }}
                    </div>
                @endif

                <form method="POST" action="{{ $authRoute }}" class="mt-8 space-y-5">
                    @csrf
                    <input type="hidden" name="lang" value="{{ $lang }}">

                    <div>
                        <label for="username" class="auth-label">Username</label>
                        <input
                            id="username"
                            name="username"
                            type="text"
                            required
                            value="{{ old('username') }}"
                            class="auth-input"
                            placeholder="Enter your username"
                        >
                    </div>

                    <div>
                        <label for="password" class="auth-label">Password</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            class="auth-input"
                            placeholder="Enter your password"
                        >
                    </div>

                    <button type="submit" class="auth-button">
                        Open {{ ucfirst($documentType) }}
                    </button>
                </form>

                <p class="mt-6 text-xs leading-6 text-slate-500">
                    If your credentials were shared separately, use them exactly as provided. Access is only available for published documents.
                </p>
            </div>
        </section>
    </div>
</main>
</body>
</html>
