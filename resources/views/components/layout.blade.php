@props([
    'locale' => 'en',
    'title' => '',
    'company' => null,
    'pdfMode' => false,
    'slug' => null,
    'langRoute' => null,
    'pdfRoute' => null,
    'editUrl' => null,
    'activateTranslation' => false,
    'isDraft' => false,
    'fullWidth' => false,
    'sansOnly' => false,
])
@php
    use Illuminate\Support\Facades\Vite;

    $bodyClass = $pdfMode
        ? 'bg-white p-0'
        : ($fullWidth
            ? 'bg-slate-100 p-3 md:p-6 xl:p-8'
            : 'bg-slate-100 px-4 py-6 md:px-8 md:py-8');
    $containerClass = $pdfMode
        ? 'mx-auto w-full max-w-[180mm] space-y-6'
        : ($fullWidth
            ? 'document-layout-full mx-auto w-full space-y-6'
            : 'mx-auto w-full max-w-[210mm] space-y-6');
    $trackingSetting = ! $pdfMode && auth()->guest()
        ? rescue(fn () => app(\App\Settings\TrackingSettings::class), report: false)
        : null;
    $pdfRouteParameters = ['slug' => $slug];

    if ($activateTranslation) {
        $pdfRouteParameters['lang'] = $locale;
    }
@endphp
<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="googlebot" content="noindex, nofollow, noarchive">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @if($sansOnly)
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700&display=swap" rel="stylesheet">
    @else
        <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Montserrat:wght@200;300;400;500;600&display=swap" rel="stylesheet">
    @endif
    @if($pdfMode)
        <style>{!! Vite::content('resources/css/app.css') !!}</style>
    @else
        @vite('resources/css/app.css')
    @endif
    <style>
        :root { --color-primary: {{ $company?->color_primary ?? '#0f172a' }}; --color-secondary: {{ $company?->color_secondary ?? '#334155' }}; }
        .brand-title { color: var(--color-primary); }
        .brand-bg { background: linear-gradient(90deg, var(--color-primary), var(--color-secondary)); }
        @media print {
            .no-print { display: none !important; }
            @page { margin: 15mm; }
            section { break-inside: avoid; }
        }
    </style>
    @if($trackingSetting)
        <x-tracking-snippets placement="head" :tracking-setting="$trackingSetting" />
    @endif
</head>
<body class="{{ $bodyClass }}">
@if($trackingSetting)
    <x-tracking-snippets placement="body" :tracking-setting="$trackingSetting" />
@endif
<div class="{{ $containerClass }}">
    @if(! $pdfMode && $isDraft)
        <div class="no-print rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-center text-sm font-medium text-amber-800">
            This document is a <span class="uppercase tracking-wide font-bold">Draft</span> and has not been published yet.
        </div>
    @endif
    @if(! $pdfMode && $slug && $langRoute && $pdfRoute)
        <div class="document-toolbar no-print flex justify-end gap-3">
            @auth
                @if($editUrl)
                    <a class="rounded-lg bg-green-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm transition hover:bg-green-700"
                        href="{{ $editUrl }}">
                        Edit Document
                    </a>
                @endif
            @endauth
            <a class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                href="{{ route($pdfRoute, $pdfRouteParameters) }}">
                Download PDF
            </a>
        </div>
    @endif

    {{ $slot }}
</div>
</body>
</html>
