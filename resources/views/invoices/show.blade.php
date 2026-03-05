@php
    $company = $invoice->company;
    $companyLogo = $company?->logo;
    $pdfMode = (bool) ($pdf ?? false);

    $toMoney = function (mixed $value) use ($invoice): string {
        return 'Rp. ' . number_format((float) ($value ?? 0), 0, ',', '.');
    };

    $fallbackLogoPath = 'Logo-Imajiner-Baru-Black-1024x245.png';
    $fallbackLogoUrl = file_exists(storage_path('app/public/' . $fallbackLogoPath))
        ? asset('storage/' . $fallbackLogoPath)
        : null;
    $logoUrl = is_string($companyLogo) && $companyLogo !== ''
        ? (str_starts_with($companyLogo, 'http') ? $companyLogo : asset('storage/' . ltrim($companyLogo, '/')))
        : $fallbackLogoUrl;
    $companyEmails = collect([$company?->email_1, $company?->email_2])
        ->filter(fn ($value) => filled($value))
        ->values()
        ->all();
    $companyPhones = collect([$company?->phone_1, $company?->phone_2])
        ->filter(fn ($value) => filled($value))
        ->values()
        ->all();
    $companyWebsiteUrl = filled($company?->website)
        ? (str_starts_with((string) $company->website, 'http://') || str_starts_with((string) $company->website, 'https://')
            ? (string) $company->website
            : 'https://' . ltrim((string) $company->website, '/'))
        : null;

    $valueByLocale = function (mixed $value) use ($locale): mixed {
        if (is_array($value) && array_key_exists($locale, $value)) {
            return $value[$locale];
        }

        return $value;
    };

    $asRows = function (mixed $value) use ($valueByLocale): array {
        $resolved = $valueByLocale($value);

        if (is_string($resolved)) {
            $decoded = json_decode($resolved, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $resolved = $decoded;
            }
        }

        if (! is_array($resolved)) {
            return [];
        }

        return collect($resolved)
            ->filter(function ($row) {
                if (! is_array($row)) {
                    return filled($row);
                }

                foreach ($row as $item) {
                    if (filled(is_string($item) ? trim($item) : $item)) {
                        return true;
                    }
                }

                return false;
            })
            ->values()
            ->all();
    };
    $asHtml = function (mixed $value) use ($valueByLocale): string {
        $resolved = $valueByLocale($value);

        return is_string($resolved) ? trim($resolved) : '';
    };

    $items = $asRows($invoice->items);
    $additionalInfoHtml = $asHtml($invoice->additional_info);

    $badgeColor = match ($invoice->payment_status?->value) {
        'paid' => 'bg-green-100 text-green-700',
        'partially_paid' => 'bg-blue-100 text-blue-700',
        'overdue' => 'bg-red-100 text-red-700',
        'cancelled' => 'bg-slate-200 text-slate-700',
        default => 'bg-yellow-100 text-yellow-700',
    };

    $bankRows = collect($company?->bank ?? [])
        ->filter(fn ($row) => filled($row['bank_name'] ?? null) || filled($row['account_name'] ?? null) || filled($row['account_number'] ?? null))
        ->values()
        ->all();

    $footerTextHtml = trim((string) ($company?->getTranslation('footer_text', $locale, false) ?? ''));
@endphp

<x-layout :locale="$locale" :title="$invoice->document_number" :company="$company" :pdf-mode="$pdfMode" :slug="$slug"
    lang-route="invoice.show" pdf-route="pdf.invoice">
    <div class="proposal-frame proposal-doc mx-auto w-full max-w-[1000px] space-y-8">
        <section class="proposal-cover p-10 md:p-24 avoid-page-break">
            <div class="flex flex-col items-start justify-between gap-8 md:flex-row">
                <div class="space-y-3">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $company?->brand_name }}" class="h-8 object-contain">
                    @endif
                    <div class="space-y-2">
                        @if($invoice->issue_date)
                            <p class="cover-meta">Issue Date — <span>{{ $invoice->issue_date->format('d M Y') }}</span></p>
                        @endif
                        @if($invoice->due_date)
                            <p class="cover-meta">Due Date — <span>{{ $invoice->due_date->format('d M Y') }}</span></p>
                        @endif
                        <p class="cover-meta">No. — <span>{{ $invoice->document_number }}</span></p>
                        <p>
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $badgeColor }}">
                                {{ $invoice->payment_status?->getLabel() ?? '-' }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="space-y-1 text-sm text-neutral-600 md:text-right">
                    @if(filled($company?->company_name))
                        <p class="text-neutral-900">{{ $company->company_name }}</p>
                    @endif
                    @if(filled($company?->address))
                        <p>{{ $company->address }}</p>
                    @endif
                    @if(count($companyEmails))
                        <p>
                            @foreach($companyEmails as $email)
                                <a href="mailto:{{ $email }}" class="hover:text-neutral-900">{{ $email }}</a>@if(!$loop->last) | @endif
                            @endforeach
                        </p>
                    @endif
                    @if(count($companyPhones))
                        <p>
                            @foreach($companyPhones as $phone)
                                @php
                                    $phoneHref = preg_replace('/[^0-9+]/', '', (string) $phone);
                                @endphp
                                <a href="tel:{{ $phoneHref }}" class="hover:text-neutral-900">{{ $phone }}</a>@if(!$loop->last) | @endif
                            @endforeach
                        </p>
                    @endif
                    @if(filled($companyWebsiteUrl))
                        <p>
                            <a href="{{ $companyWebsiteUrl }}" target="_blank" rel="noreferrer"
                                class="hover:text-neutral-900">{{ $company->website }}</a>
                        </p>
                    @endif
                </div>
            </div>
            <div class="mt-10 grid gap-6 md:grid-cols-2">
                <div>
                    <span class="proposal-kicker mb-2 block text-[10px] font-medium uppercase tracking-[0.3em]">Invoice To</span>
                    <h1 class="proposal-serif text-3xl text-neutral-900">{{ $invoice->client_company }}</h1>
                    <p class="mt-2 text-sm text-neutral-600">{{ $invoice->client_name }}</p>
                    <p class="text-sm text-neutral-600">{{ $invoice->client_email }}</p>
                    @if(filled($invoice->client_phone))
                        <p class="text-sm text-neutral-600">{{ $invoice->client_phone }}</p>
                    @endif
                </div>
                <div class="rounded-2xl border border-neutral-200 bg-white p-6">
                    <p class="text-sm text-neutral-600">Subtotal</p>
                    <p class="proposal-serif text-xl text-neutral-900">{{ $toMoney($invoice->subtotal) }}</p>
                    <p class="mt-3 text-sm text-neutral-600">Tax ({{ number_format((float) $invoice->tax_rate, 2) }}%)</p>
                    <p class="proposal-serif text-lg text-neutral-900">{{ $toMoney($invoice->tax_amount) }}</p>
                    <p class="mt-4 border-t border-neutral-200 pt-4 text-sm text-neutral-600">Total</p>
                    <p class="proposal-serif text-2xl text-neutral-900">{{ $toMoney($invoice->total) }}</p>
                </div>
            </div>
        </section>

        <section class="px-6 pb-10 pt-0 md:px-24 avoid-page-break">
            <span class="proposal-kicker mb-3 block text-[10px] font-medium uppercase tracking-[0.3em]">Items</span>
            <div class="overflow-hidden rounded-2xl border border-neutral-200">
                <table class="w-full text-sm">
                    <thead class="bg-neutral-100 text-neutral-700">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Title</th>
                            <th class="px-4 py-3 text-left font-medium">Description</th>
                            <th class="px-4 py-3 text-right font-medium">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr class="border-t border-neutral-200 align-top">
                                <td class="px-4 py-3">{{ $item['title'] ?? '-' }}</td>
                                <td class="px-4 py-3 text-neutral-600">{{ $item['description'] ?? '-' }}</td>
                                <td class="px-4 py-3 text-right font-medium">{{ $toMoney($item['price'] ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-3 text-neutral-500" colspan="3">No items available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if($additionalInfoHtml !== '')
            <section class="px-6 pb-10 pt-0 md:px-24">
                <span class="proposal-kicker mb-3 block text-[10px] font-medium uppercase tracking-[0.3em]">Additional Info</span>
                <div class="prose prose-sm max-w-none text-neutral-700">
                    {!! $additionalInfoHtml !!}
                </div>
            </section>
        @endif

        @if(! empty($bankRows) || filled($footerTextHtml))
            <section class="px-6 pb-10 pt-0 text-sm text-neutral-600 md:px-24">
                @if(filled($footerTextHtml))
                    <div class="prose prose-sm max-w-none">{!! $footerTextHtml !!}</div>
                @endif

                @if(! empty($bankRows))
                    <div class="mt-4">
                        <p class="mb-2 font-semibold text-neutral-900">Bank Details</p>
                        <div class="space-y-1">
                            @foreach($bankRows as $bank)
                                <p>
                                    {{ $bank['bank_name'] ?? '-' }} - {{ $bank['account_number'] ?? '-' }}
                                    ({{ $bank['account_name'] ?? '-' }})
                                </p>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
        @endif
    </div>
</x-layout>

