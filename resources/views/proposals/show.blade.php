@php
    $company = $proposal->company;
    $toMoney = fn ($value) => $proposal->currency . ' ' . number_format((float) ($value ?? 0), 2);
    $pdfMode = (bool) ($pdf ?? false);
    $logoUrl = is_string($company?->logo) && str_starts_with($company->logo, 'http') ? $company->logo : null;

    $list = function (?array $items, string $key): array {
        return collect($items ?? [])->pluck($key)->filter()->values()->all();
    };
@endphp
<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $proposal->document_number }}</title>
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
    @if(! $pdfMode)
    <div class="no-print flex justify-end gap-2">
        <a class="rounded border px-3 py-1 text-sm {{ $locale === 'en' ? 'bg-slate-900 text-white' : '' }}" href="{{ route('proposal.show', ['slug' => $slug, 'lang' => 'en']) }}">EN</a>
        <a class="rounded border px-3 py-1 text-sm {{ $locale === 'id' ? 'bg-slate-900 text-white' : '' }}" href="{{ route('proposal.show', ['slug' => $slug, 'lang' => 'id']) }}">ID</a>
        <a class="rounded border px-3 py-1 text-sm" href="{{ route('pdf.proposal', ['slug' => $slug, 'lang' => $locale]) }}">Download PDF</a>
    </div>
    @endif

    <section class="border-b pb-4">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $company?->brand_name }}" class="h-12 w-12 object-contain">
                @endif
                <div>
                    <h1 class="text-2xl font-bold brand-title">{{ $company?->brand_name ?? $company?->company_name }}</h1>
                    <p class="text-sm text-slate-500">{{ $proposal->document_number }}</p>
                </div>
            </div>
            <div class="text-right text-sm">
                <p>Issue: {{ optional($proposal->issue_date)->format('d M Y') }}</p>
                <p>Valid until: {{ optional($proposal->valid_until)->format('d M Y') ?? 'No expiry' }}</p>
            </div>
        </div>
    </section>

    <section><h2 class="mb-2 font-semibold">Client Info</h2><p>{{ $proposal->client_company }} — {{ $proposal->client_name }}</p><p>{{ $proposal->client_email }} | {{ $proposal->client_phone }}</p></section>
    <section><h2 class="mb-2 font-semibold">Brief</h2><ul class="list-disc pl-5">@foreach($list($proposal->brief, 'content') as $row)<li>{{ $row }}</li>@endforeach</ul></section>

    <section>
        <h2 class="mb-2 font-semibold">Portfolios</h2>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            @forelse($proposal->portfolios as $portfolio)
                <div class="rounded border p-3"><p class="font-medium">{{ $portfolio->name }}</p><a class="text-sm text-blue-600" href="{{ $portfolio->url_link }}">{{ $portfolio->url_link }}</a></div>
            @empty
                <p class="text-sm text-slate-500">No portfolio items.</p>
            @endforelse
        </div>
    </section>

    <section><h2 class="mb-2 font-semibold">Core Services</h2><ul class="list-disc pl-5">@foreach($list($proposal->core_services, 'service') as $row)<li>{{ $row }}</li>@endforeach</ul></section>
    <section><h2 class="mb-2 font-semibold">Features</h2>@foreach(($proposal->features ?? []) as $feature)<div class="mb-2"><p class="font-medium">{{ $feature['feature_name'] ?? '-' }}</p><p class="text-sm text-slate-600">{{ $feature['feature_description'] ?? '' }}</p></div>@endforeach</section>
    <section><h2 class="mb-2 font-semibold">Server</h2><ul class="list-disc pl-5">@foreach($list($proposal->server, 'item') as $row)<li>{{ $row }}</li>@endforeach</ul></section>
    <section><h2 class="mb-2 font-semibold">Assets</h2><ul class="list-disc pl-5">@foreach($list($proposal->assets, 'item') as $row)<li>{{ $row }}</li>@endforeach</ul></section>
    <section><h2 class="mb-2 font-semibold">Security</h2><ul class="list-disc pl-5">@foreach($list($proposal->security, 'item') as $row)<li>{{ $row }}</li>@endforeach</ul></section>
    <section><h2 class="mb-2 font-semibold">Support</h2><ul class="list-disc pl-5">@foreach($list($proposal->support, 'item') as $row)<li>{{ $row }}</li>@endforeach</ul></section>

    <section>
        <h2 class="mb-2 font-semibold">Offer 1</h2>
        <p>{{ $proposal->offer_name_1 }}</p>
        <p>{!! $proposal->offer_1_original_price && $proposal->offer_1_original_price > $proposal->offer_1_price ? '<span class="line-through text-slate-500 mr-1">'.$toMoney($proposal->offer_1_original_price).'</span>' : '' !!}{{ $toMoney($proposal->offer_1_price) }}</p>
        <p>Renewal: {!! $proposal->offer_1_original_renewal_price && $proposal->offer_1_original_renewal_price > $proposal->offer_1_renewal_price ? '<span class="line-through text-slate-500 mr-1">'.$toMoney($proposal->offer_1_original_renewal_price).'</span>' : '' !!}{{ $toMoney($proposal->offer_1_renewal_price) }}</p>
    </section>

    @if($proposal->offer_name_2)
    <section>
        <h2 class="mb-2 font-semibold">Offer 2</h2>
        <p>{{ $proposal->offer_name_2 }}</p>
        <p>{!! $proposal->offer_2_original_price && $proposal->offer_2_original_price > $proposal->offer_2_price ? '<span class="line-through text-slate-500 mr-1">'.$toMoney($proposal->offer_2_original_price).'</span>' : '' !!}{{ $toMoney($proposal->offer_2_price) }}</p>
        <p>Renewal: {!! $proposal->offer_2_original_renewal_price && $proposal->offer_2_original_renewal_price > $proposal->offer_2_renewal_price ? '<span class="line-through text-slate-500 mr-1">'.$toMoney($proposal->offer_2_original_renewal_price).'</span>' : '' !!}{{ $toMoney($proposal->offer_2_renewal_price) }}</p>
    </section>
    @endif

    <section><h2 class="mb-2 font-semibold">Additional Benefit</h2><ul class="list-disc pl-5">@foreach($list($proposal->additional_benefit, 'item') as $row)<li>{{ $row }}</li>@endforeach</ul></section>
    <section><h2 class="mb-2 font-semibold">Add-Ons</h2>@foreach(($proposal->add_on ?? []) as $addOn)<div class="mb-2"><p class="font-medium">{{ $addOn['item_name'] ?? '-' }} — {{ $toMoney($addOn['item_price'] ?? 0) }}</p><p class="text-sm text-slate-600">{{ $addOn['item_description'] ?? '' }}</p></div>@endforeach</section>
    <section><h2 class="mb-2 font-semibold">Payment Terms</h2>@foreach(($proposal->payment ?? []) as $payment)<p>{{ $payment['payment_name'] ?? '' }} {{ isset($payment['payment_percentage']) ? '(' . $payment['payment_percentage'] . '%)' : '' }}</p>@endforeach</section>
    <section><h2 class="mb-2 font-semibold">Terms & Conditions</h2>@foreach(($proposal->terms_condition ?? []) as $term)<div class="mb-2"><p class="font-medium">{{ $term['title'] ?? '' }}</p><p class="text-sm text-slate-600">{{ $term['description'] ?? '' }}</p></div>@endforeach</section>

    <section class="rounded border p-4">
        <h2 class="mb-2 font-semibold">Tax Summary</h2>
        <p>Tax rate: {{ $proposal->tax_rate }}%</p>
        <p>Tax amount: {{ $toMoney($proposal->tax_amount) }}</p>
        <p class="font-semibold">Total: {{ $toMoney($proposal->total_amount) }}</p>
    </section>

    <section class="border-t pt-4 text-sm text-slate-600">
        <p>{{ $company?->getTranslation('footer_text', $locale, false) ?? '' }}</p>
        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
            <div>
                <p class="font-medium text-slate-800">Bank Details</p>
                @foreach(($company?->bank ?? []) as $bank)
                    <p>{{ $bank['bank_name'] ?? '-' }} - {{ $bank['account_number'] ?? '-' }} ({{ $bank['account_name'] ?? '-' }})</p>
                @endforeach
            </div>
            <div>
                <p class="font-medium text-slate-800">PIC</p>
                @foreach(($company?->pic ?? []) as $pic)
                    <p>{{ $pic['pic_name'] ?? '-' }} ({{ $pic['pic_role'] ?? '-' }})</p>
                @endforeach
            </div>
        </div>
    </section>
</div>
</body>
</html>
