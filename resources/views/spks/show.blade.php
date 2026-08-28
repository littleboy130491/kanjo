@php
    $company = $spk->company;
    $companyLogo = $company?->logo;
    $pdfMode = (bool) ($pdf ?? false);
    $editUrl = auth()->check()
        ? \App\Filament\Admin\Resources\Spks\SpkResource::getUrl('edit', ['record' => $spk])
        : null;
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
    $valueByLocale = function (mixed $value) use ($locale): mixed {
        if (is_array($value) && array_key_exists($locale, $value)) {
            return $value[$locale];
        }

        return $value;
    };
    $asHtml = function (mixed $value) use ($valueByLocale): string {
        $resolved = $valueByLocale($value);

        return is_string($resolved) ? trim($resolved) : '';
    };
    $contentHtml = $asHtml($spk->content);
    $contentHtml = preg_replace(
        '/<table(?![^>]*\bspk-timeline-table\b)([^>]*)>\s*(<thead)/i',
        '<table class="spk-timeline-table"$1>$2',
        $contentHtml,
    ) ?? $contentHtml;
    $titleHtml = \App\Services\SpkTemplateRenderer::displayHtml('title', $spk, $locale);
    $partyIdentificationHtml = \App\Services\SpkTemplateRenderer::displayHtml('party_identification', $spk, $locale);
@endphp

<x-layout :locale="$locale" :title="$spk->document_number" :company="$company" :pdf-mode="$pdfMode" :slug="$slug"
    :edit-url="$editUrl" :activate-translation="true" :is-draft="$spk->status->value === 'draft'" lang-route="spk.show" pdf-route="pdf.spk">
    <div class="document-frame document-view document-shell spk-document pt-10 pb-10 md:pt-24 md:pb-24">
        <section class="document-section-pad avoid-page-break">
            <div class="invoice-row invoice-row-logo">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $company?->brand_name }}" class="h-8 object-contain">
                @endif
            </div>

            <div class="invoice-row invoice-meta-grid">
                <div class="invoice-company-block invoice-issuer-block">
                    @if(filled($company?->company_name))
                        <p class="mb-2">{{ $company->company_name }}</p>
                    @elseif(filled($spk->company_name))
                        <p class="mb-2">{{ $spk->company_name }}</p>
                    @endif
                    @if(filled($company?->address))
                        <p class="mb-2">{!! $company->address !!}</p>
                    @elseif(filled($spk->company_address))
                        <p class="mb-2">{!! nl2br(e($spk->company_address)) !!}</p>
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
            </div>
        </section>

        <section class="document-section-pad spk-content-section">
            <div class="spk-heading spk-cover">
                <div class="spk-title document-richtext">
                    {!! $titleHtml !!}
                </div>
                <p class="spk-number"><strong>Nomor {{ $spk->document_number }}</strong></p>
            </div>

            <div class="document-richtext spk-content">
                {!! $partyIdentificationHtml !!}
                {!! $contentHtml !!}
            </div>
        </section>

        <section class="document-section-pad spk-signature-section avoid-page-break">
            <p class="spk-approval-label">Menyetujui,</p>
            <div class="spk-signature-grid">
                <div class="spk-signature-block">
                    <p class="spk-signature-party">PIHAK PERTAMA</p>
                    <div class="spk-signature-space"></div>
                    <p class="spk-signature-name">{{ $spk->client_pic_name }}</p>
                    <p>{{ $spk->client_company }}</p>
                </div>
                <div class="spk-signature-block">
                    <p class="spk-signature-party">PIHAK KEDUA</p>
                    <div class="spk-signature-space"></div>
                    <p class="spk-signature-name">{{ $spk->company_pic_name }}</p>
                    <p>{{ $spk->company_name }}</p>
                </div>
            </div>
        </section>
    </div>
</x-layout>
