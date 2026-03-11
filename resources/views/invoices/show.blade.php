@php
    $company = $invoice->company;
    $companyLogo = $company?->logo;
    $pdfMode = (bool) ($pdf ?? false);
    $activateTranslation = (bool) $invoice->activate_translation;
    $storageFileToDataUri = function (?string $path): ?string {
        if (! filled($path)) {
            return null;
        }

        try {
            $disk = \Illuminate\Support\Facades\Storage::disk(config('curator.default_disk', 'public'));

            if (! $disk->exists($path)) {
                return null;
            }

            $mimeType = $disk->mimeType($path) ?: 'application/octet-stream';

            return 'data:' . $mimeType . ';base64,' . base64_encode($disk->get($path));
        } catch (\Throwable) {
            return null;
        }
    };

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
    $editUrl = auth()->check()
        ? \App\Filament\Admin\Resources\Invoices\InvoiceResource::getUrl('edit', ['record' => $invoice])
        : null;
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
    $companyWebsiteLabel = filled($company?->website)
        ? preg_replace('#^https?://#i', '', (string) $company->website)
        : null;
    $pdfRouteParameters = ['slug' => $slug];

    if ($activateTranslation) {
        $pdfRouteParameters['lang'] = $locale;
    }

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
    $present = function (mixed $value): bool {
        if (is_array($value)) {
            return count($value) > 0;
        }

        if (! is_string($value)) {
            return filled($value);
        }

        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\x{00A0}\x{200B}-\x{200D}\x{FEFF}]/u', '', $text) ?? $text;

        return trim($text) !== '';
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

    $preparedBy = collect($company?->pic ?? [])
        ->first(function ($row) {
            if (! is_array($row)) {
                return false;
            }

            return strcasecmp(trim((string) ($row['pic_role'] ?? '')), 'Finance Manager') === 0;
        });

    $preparedBySignatureUrl = null;

    if (is_array($preparedBy)) {
        $picSign = $preparedBy['pic_sign'] ?? null;
        $preparedByMedia = null;

        if ($picSign instanceof \Awcodes\Curator\Models\Media) {
            $preparedByMedia = $picSign;
        } elseif (is_array($picSign) && filled($picSign['id'] ?? null)) {
            $preparedByMedia = \Awcodes\Curator\Models\Media::find($picSign['id']);
        } elseif (is_numeric($picSign)) {
            $preparedByMedia = \Awcodes\Curator\Models\Media::find((int) $picSign);
        } elseif (is_string($picSign) && filled($picSign) && is_numeric($picSign)) {
            $preparedByMedia = \Awcodes\Curator\Models\Media::find((int) $picSign);
        }

        if ($preparedByMedia?->path) {
            $preparedBySignatureUrl = $pdfMode
                ? ($storageFileToDataUri($preparedByMedia->path) ?? asset('storage/' . ltrim($preparedByMedia->path, '/')))
                : glide_builder()
                    ->width(240)
                    ->height(120)
                    ->fit('contain')
                    ->toUrl($preparedByMedia->path);
        } elseif (is_array($picSign) && filled($picSign['path'] ?? null)) {
            $preparedBySignatureUrl = $pdfMode
                ? ($storageFileToDataUri($picSign['path']) ?? asset('storage/' . ltrim((string) $picSign['path'], '/')))
                : glide_builder()
                    ->width(240)
                    ->height(120)
                    ->fit('contain')
                    ->toUrl($picSign['path']);
        } elseif (is_string($picSign) && filled($picSign) && ! is_numeric($picSign)) {
            $preparedBySignatureUrl = $pdfMode && ! str_starts_with($picSign, 'http')
                ? ($storageFileToDataUri($picSign) ?? asset('storage/' . ltrim($picSign, '/')))
                : $picSign;
        }
    }

    $footerTextHtml = trim((string) ($company?->getTranslation('footer_text', $locale, false) ?? ''));
@endphp

<x-layout :locale="$locale" :title="$invoice->document_number" :company="$company" :pdf-mode="$pdfMode" :slug="$slug"
    :edit-url="$editUrl" :activate-translation="$activateTranslation" lang-route="invoice.show" pdf-route="pdf.invoice">
    <div class="document-frame document-view document-shell">
        <section class="document-section-pad pt-10 md:pt-24 avoid-page-break">
            <div class="invoice-row invoice-row-logo">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $company?->brand_name }}" class="h-8 object-contain">
                @endif
            </div>

            <h1 class="document-accent mb-3 block text-sm uppercase tracking-[0.3em] font-bold">Invoice</h1>
            <div class="invoice-row invoice-meta-grid">
                <div class="invoice-company-block invoice-issuer-block">
                        @if(filled($company?->company_name))
                            <p class="mb-2">{{ $company->company_name }}</p>
                        @endif
                        @if(filled($company?->address))
                            <p class="mb-2">{!!  $company->address !!}</p>
                        @endif
                        @if(count($companyEmails))
                            <div class="invoice-contact-row">
                                <svg class="invoice-contact-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M4 7h16v10H4V7z" stroke="currentColor" stroke-width="1.6" />
                                    <path d="M4 8l8 6 8-6" stroke="currentColor" stroke-width="1.6" />
                                </svg>
                                <span class="invoice-contact-list">
                                    @foreach($companyEmails as $email)
                                        <a href="mailto:{{ $email }}" class="invoice-contact-link">{{ $email }}</a>@if(!$loop->last) | @endif
                                    @endforeach
                                </span>
                            </div>
                        @endif
                        @if(count($companyPhones))
                            <div class="invoice-contact-row">
                                <svg class="invoice-contact-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path
                                        d="M7.5 4h3l1.4 3.6-1.8 1.8a14 14 0 0 0 4.3 4.3l1.8-1.8L20 13.5v3a2 2 0 0 1-2.2 2c-6.4-.7-11.6-5.9-12.3-12.3A2 2 0 0 1 7.5 4z"
                                        stroke="currentColor" stroke-width="1.6" />
                                </svg>
                                <span class="invoice-contact-list">
                                    @foreach($companyPhones as $phone)
                                        @php
                                            $phoneHref = preg_replace('/[^0-9+]/', '', (string) $phone);
                                        @endphp
                                        <a href="tel:{{ $phoneHref }}" class="invoice-contact-link">{{ $phone }}</a>@if(!$loop->last) | @endif
                                    @endforeach
                                </span>
                            </div>
                        @endif
                    </div>
                <div class="invoice-document-block">
                    <div class="document-meta-stack space-y-2">
                        @if($invoice->issue_date)
                            <p class="cover-meta invoice-cover-meta">Issue Date — <span>{{ $invoice->issue_date->format('d M Y') }}</span></p>
                        @endif
                        @if($invoice->due_date)
                            <p class="cover-meta invoice-cover-meta">Due Date — <span>{{ $invoice->due_date->format('d M Y') }}</span></p>
                        @endif
                        <p class="cover-meta invoice-cover-meta">No. — <span>{{ $invoice->document_number }}</span></p>
                        <p>
                            <span class="mt-2 uppercase inline-flex rounded-full px-3 py-1 text-md font-semibold {{ $badgeColor }}">
                                {{ $invoice->payment_status?->getLabel() ?? '-' }}
                            </span>
                        </p>
                    </div>
                </div>

            </div>

            
        </section>
    <section class="document-section-pad invoice-row-section avoid-page-break">
    <div class="invoice-row">
                <div class="invoice-client-block">
                    <span class="document-accent document-kicker mb-2">Invoice To</span>
                     <p class="text-md text-neutral-900">{{ $invoice->client_company }}</p>
                </div>
            </div>
    </section>
        <section class="document-section-pad invoice-row-section avoid-page-break">
            <div class="table-wrap">
                <table class="data-table invoice-items-table">
                    <thead>
                        <tr>
                            <th>Item(s)</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>
                                    <div class="invoice-item-stack">
                                        <span class="invoice-item-title">{{ $item['title'] ?? '-' }}</span>
                                        @if(filled($item['description'] ?? null))
                                            <span class="invoice-item-description">{{ $item['description'] }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $toMoney($item['price'] ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="invoice-empty-state" colspan="2">No items available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        @if((float) $invoice->tax_rate > 0 || (float) $invoice->tax_amount > 0)
                            <tr>
                                <th>Subtotal</th>
                                <th>{{ $toMoney($invoice->subtotal) }}</th>
                            </tr>
                            <tr>
                                <th>Tax ({{ number_format((float) $invoice->tax_rate, 2) }}%)</th>
                                <th>{{ $toMoney($invoice->tax_amount) }}</th>
                            </tr>
                        @endif
                        <tr>
                            <th class="total">Total</th>
                            <th class="total">{{ $toMoney($invoice->total) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        @if($present($additionalInfoHtml))
            <section class="document-section-pad invoice-row-section">
                <span class="document-accent document-kicker">Additional Info</span>
                <div class="document-richtext invoice-additional-info-content">
                    {!! $additionalInfoHtml !!}
                </div>
            </section>
        @endif

        @if(! empty($bankRows))
            <section class="document-section-pad invoice-row-section invoice-payment-info text-neutral-600">
                <span class="document-accent document-kicker">Payment Info</span>
                @if(! empty($bankRows))
                    <div class="document-bank-details">
                        <p class="mb-4">{{ __('invoice.bank_transfer_notice') }}</p>
                        <div class="document-bank-list">
                            @foreach($bankRows as $bank)
                                <div class="document-bank-entry">
                                    <p class="font-bold">{{ $bank['bank_name'] ?? '-' }}</p>
                                    <p class="font-bold">Account Number: {{ $bank['account_number'] ?? '-' }}</p>
                                    <p class="font-bold">Account Name: {{ $bank['account_name'] ?? '-' }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
        @endif

        @if(is_array($preparedBy) && (filled($preparedBySignatureUrl) || filled($preparedBy['pic_name'] ?? null) || filled($preparedBy['pic_role'] ?? null)))
            <section class="document-section-pad invoice-row-section pb-10 md:pb-24">
                <div class="invoice-prepared-by ml-auto flex min-h-36 max-w-xs flex-col items-end text-right text-sm text-neutral-600">
                    <span class="document-accent document-kicker">Prepared by</span>

                    @if(filled($preparedBySignatureUrl))
                        <img
                            src="{{ $preparedBySignatureUrl }}"
                            alt="{{ $preparedBy['pic_name'] ?? 'Finance Manager signature' }}"
                            class="mb-4 h-20 w-auto max-w-[240px] object-contain object-right"
                        >
                    @else
                        <div class="mb-4 h-20"></div>
                    @endif

                    @if(filled($preparedBy['pic_name'] ?? null))
                        <p class="font-semibold text-neutral-900">{{ $preparedBy['pic_name'] }}</p>
                    @endif

                    @if(filled($preparedBy['pic_role'] ?? null))
                        <p>{{ $preparedBy['pic_role'] }}</p>
                    @endif
                </div>
            </section>
        @endif

        @if($present($footerTextHtml))
            <section class="document-section-pad invoice-row-section pb-10 md:pb-24 invoice-footer-text text-neutral-600">
                <div class="document-richtext invoice-footer-text-content">{!! $footerTextHtml !!}</div>
            </section>
        @endif
    </div>
</x-layout>
