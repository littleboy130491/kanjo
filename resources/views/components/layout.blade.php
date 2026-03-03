@props([
    'locale' => 'en',
    'title' => '',
    'company' => null,
    'pdfMode' => false,
    'slug' => null,
    'langRoute' => null,
    'pdfRoute' => null,
])
@php
    $bodyClass = $pdfMode
        ? 'bg-white p-0'
        : 'bg-slate-100 px-4 py-6 md:px-8 md:py-8';
@endphp
<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Montserrat:wght@200;300;400;500;600&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --color-primary: {{ $company?->color_primary ?? '#0f172a' }}; --color-secondary: {{ $company?->color_secondary ?? '#334155' }}; }
        .brand-title { color: var(--color-primary); }
        .brand-bg { background: linear-gradient(90deg, var(--color-primary), var(--color-secondary)); }
        @media print {
            .no-print { display: none !important; }
            @page { margin: 20mm; }
            section { break-inside: avoid; }
        }
    </style>
</head>
<body class="{{ $bodyClass }}">
<div class="mx-auto w-full max-w-[210mm] space-y-6">
    @if(! $pdfMode && $slug && $langRoute && $pdfRoute)
        <div class="no-print flex justify-end">
            <a class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                href="{{ route($pdfRoute, ['slug' => $slug, 'lang' => $locale]) }}">
                Download PDF
            </a>
        </div>
    @endif

    {{ $slot }}
</div>
</body>
</html>
