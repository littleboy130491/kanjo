@props([
    'locale' => 'en',
    'title' => '',
    'company' => null,
    'pdfMode' => false,
    'slug' => null,
    'langRoute' => null,
    'pdfRoute' => null,
])
<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
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
<body class="bg-slate-100 p-4 md:p-8">
<div class="mx-auto max-w-5xl space-y-6 rounded-xl bg-white p-6 shadow">
    @if(! $pdfMode && $slug && $langRoute && $pdfRoute)
        <div class="no-print flex justify-end gap-2">
            <a class="rounded border px-3 py-1 text-sm" href="{{ route($pdfRoute, ['slug' => $slug, 'lang' => $locale]) }}">Download PDF</a>
        </div>
    @endif

    {{ $slot }}
</div>
</body>
</html>
