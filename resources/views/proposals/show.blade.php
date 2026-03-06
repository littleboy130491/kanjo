@php
    $company = $proposal->company;
    $companyLogo = $company?->logo;
    $pdfMode = (bool) ($pdf ?? false);

    $toMoney = function (mixed $value) use ($proposal): string {
        return 'Rp. ' . number_format((float) ($value ?? 0), 0, ',', '.');
    };

    $fallbackLogoPath = 'Logo-Imajiner-Baru-Black-1024x245.png';
    $fallbackLogoUrl = file_exists(storage_path('app/public/' . $fallbackLogoPath))
        ? asset('storage/' . $fallbackLogoPath)
        : null;
    $footerBarcodePath = 'qrcode_imajiner.id.png';
    $footerBarcodeUrl = file_exists(storage_path('app/public/' . $footerBarcodePath))
        ? asset('storage/' . $footerBarcodePath)
        : null;
    $companyWebsiteUrl = filled($company?->website)
        ? (str_starts_with((string) $company->website, 'http://') || str_starts_with((string) $company->website, 'https://')
            ? (string) $company->website
            : 'https://' . ltrim((string) $company->website, '/'))
        : null;
    $logoUrl = is_string($companyLogo) && $companyLogo !== ''
        ? (str_starts_with($companyLogo, 'http') ? $companyLogo : asset('storage/' . ltrim($companyLogo, '/')))
        : $fallbackLogoUrl;

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

    $asRows = function (mixed $value) use ($valueByLocale): array {
        $resolved = $valueByLocale($value);

        if (is_string($resolved)) {
            $decoded = json_decode($resolved, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $resolved = $decoded;
            }
        }

        if (!is_array($resolved)) {
            return [];
        }

        return collect($resolved)
            ->filter(function ($row) {
                if (!is_array($row)) {
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

    $present = function (mixed $value): bool {
        if (is_array($value)) {
            return count($value) > 0;
        }

        if (!is_string($value)) {
            return filled($value);
        }

        return trim(strip_tags($value)) !== '';
    };

    $briefHtml = $asHtml($proposal->brief);
    $extraContentBriefHtml = $asHtml($proposal->extra_content_brief);
    $coreServicesHtml = $asHtml($proposal->core_services);
    $featuresHtml = $asHtml($proposal->features);
    $serverHtml = $asHtml($proposal->server);
    $assetsHtml = $asHtml($proposal->assets);
    $securityHtml = $asHtml($proposal->security);
    $supportHtml = $asHtml($proposal->support);
    $additionalBenefitHtml = $asHtml($proposal->additional_benefit);
    $paymentHtml = $asHtml($proposal->payment);
    $termsConditionHtml = $asHtml($proposal->terms_condition);
    $additionalInfoHtml = $asHtml($proposal->additional_info);
    $footerTextHtml = trim((string) ($company?->getTranslation('footer_text', $locale, false) ?? ''));

    $offer1Timeline = $asRows($proposal->offer_1_project_timeline);
    $offer2Timeline = $asRows($proposal->offer_2_project_timeline);
    $addOns = $asRows($proposal->add_on);
    $hasOffer2 = filled($proposal->offer_name_2) || filled($proposal->offer_2_price) || filled($proposal->offer_2_renewal_price);

    $bankRows = collect($company?->bank ?? [])
        ->filter(fn($row) => filled($row['bank_name'] ?? null) || filled($row['account_name'] ?? null) || filled($row['account_number'] ?? null))
        ->values()
        ->all();

    $picRows = collect($company?->pic ?? [])
        ->filter(fn($row) => filled($row['pic_name'] ?? null) || filled($row['pic_role'] ?? null))
        ->values()
        ->all();
    $companyEmails = collect([$company?->email_1, $company?->email_2])
        ->filter(fn($value) => filled($value))
        ->values()
        ->all();
    $companyPhones = collect([$company?->phone_1, $company?->phone_2])
        ->filter(fn($value) => filled($value))
        ->values()
        ->all();

    $sectionNo = 1;
@endphp
<x-layout :locale="$locale" :title="$proposal->document_number" :company="$company" :pdf-mode="$pdfMode" :slug="$slug"
    lang-route="proposal.show" pdf-route="pdf.proposal">
    <div class="proposal-frame proposal-doc document-shell">
        <section class="proposal-cover document-cover-pad avoid-page-break">
            <div class="proposal-cover-header flex flex-col items-start justify-between gap-8 md:flex-row">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $company?->brand_name }}" class="h-8 object-contain">
                @endif
                <div class="document-meta-stack">
                    @if($proposal->issue_date)
                        <p class="cover-meta">Date — <span>{{ $proposal->issue_date->format('d M Y') }}</span></p>
                    @endif
                    <p class="cover-meta">No. — <span>{{ $proposal->document_number }}</span></p>
                </div>
            </div>

            <div class="my-8 md:my-8">
                <span class="proposal-kicker document-kicker mb-6">Project
                    Proposal</span>
                <h1 class="cover-title proposal-serif">
                    Website<br>
                    <span class="cover-subtitle">Design & Development</span>
                </h1>
            </div>

            @if(filled($proposal->client_company))
                <div class="flex flex-col items-end justify-between gap-8 pt-8 md:flex-row">
                    <div>
                        <p class="document-kicker text-neutral-400">Prepared For</p>
                        <p class="text-xl md:text-2xl text-neutral-900 font-bold">{{ $proposal->client_company }}</p>
                    </div>
                </div>
            @endif
        </section>

        @if($present($briefHtml) || $present($extraContentBriefHtml))
            <section class="section-row allow-page-break">
                <h2 class="section-label">Dear {{ $proposal->client_name }},</h2>
                <div class="section-content">
                    @if($present($briefHtml))
                        {!! $briefHtml !!}
                    @endif
                    @if($present($extraContentBriefHtml))
                        <div class="mt-6">
                            {!! $extraContentBriefHtml !!}
                        </div>
                    @endif
                </div>
            </section>
        @endif

        @if($proposal->portfolios->isNotEmpty())
            <section class="section-row allow-page-break">
                <h2 class="section-label">Portfolio</h2>
                <div class="section-content mb-8">
                    <p>{{ __('proposal.portfolio_reference_intro') }}</p>
                </div>
                <div class="section-content portfolio-grid">
                    @foreach($proposal->portfolios as $portfolio)
                        <article class="group cursor-pointer border-0">
                            <a style="text-decoration: none !important;" href="{{ $portfolio->url_link }}" target="_blank" rel="noreferrer">
                                <div class="mb-4 aspect-[4/3] overflow-hidden bg-neutral-100">
                                    @if($portfolio->portfolio_image_url)
                                        <img src="{{ $portfolio->portfolio_image_url }}" alt="{{ $portfolio->name }}"
                                            class="h-full w-full object-cover object-top transition-transform duration-500 ease-out group-hover:scale-105">
                                    @endif
                                </div>
                                <p class="text-sm text-neutral-900 md:text-lg">{{ $portfolio->name }}</p>
                                @if(filled($portfolio->url_link))
                                    <span class="proposal-kicker mt-2 inline-block text-[10px] uppercase tracking-[0.2em]">
                                        View Live Site ->
                                    </span>
                                @endif
                            </a>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if($present($coreServicesHtml))
            <section id="services" class="section-row allow-page-break">
            
                    <h3 class="offer_name">
                        @if($hasOffer2)Option 1: @endif {{ $proposal->offer_name_1 ?: 'Main Offer' }}
                    </h3>
           
                <h2 class="section-label">Core Services</h2>
                <div class="section-content">
                    {!! $coreServicesHtml !!}
                </div>
            </section>
        @endif

        @if($present($featuresHtml))
            <section class="section-row allow-page-break">
                <h2 class="section-label">Features</h2>
                <div class="section-content">
                    {!! $featuresHtml !!}
                </div>
            </section>
        @endif

        @if($present($assetsHtml))
            <section class="section-row allow-page-break">
                <h2 class="section-label">Assets</h2>
                <div class="section-content">
                    {!! $assetsHtml !!}
                </div>
            </section>
        @endif

        @if($present($serverHtml))
            <section class="section-row allow-page-break">
                <h2 class="section-label">Server</h2>
                <div class="section-content">
                    {!! $serverHtml !!}
                </div>
            </section>
        @endif

        @if($present($securityHtml))
            <section class="section-row allow-page-break">
                <h2 class="section-label">Security</h2>
                <div class="section-content">
                    {!! $securityHtml !!}
                </div>
            </section>
        @endif

        @if($present($supportHtml))
            <section class="section-row allow-page-break">
                <h2 class="section-label">Support</h2>
                <div class="section-content">
                    {!! $supportHtml !!}
                </div>
            </section>
        @endif

        @if($hasOffer2 && $present($additionalBenefitHtml))
            <section class="section-row allow-page-break">
                <h3 class="offer_name">Option 2: {{ $proposal->offer_name_2 ?: 'Alternative Offer' }}</h3>
                <h2 class="section-label">Additional Benefits</h2>
                <div class="section-content">
                    <p>{{ __('proposal.offer_2_additional_benefit_description', ['offer' => ($proposal->offer_name_1 ?: 'Main Offer')]) }}</p>
                    {!! $additionalBenefitHtml !!}
                </div>
            </section>
        @endif

        @php
            $hasInvestment = filled($proposal->offer_name_1) || filled($proposal->offer_1_price) || filled($proposal->offer_1_renewal_price)
                || $hasOffer2;
            $hasAddOns = count($addOns) > 0;
        @endphp
        @if($hasInvestment)
            <section id="price" class="section-row avoid-page-break">
                <h2 class="section-label">Pricing</h2>
                <div class="section-content">
                    <div class="pricing-option">
                        @if($hasOffer2)
                            <span class="proposal-kicker document-kicker -mb-4">Option
                                01</span>
                        @endif
                        <h3 class="offer_name">{{ $proposal->offer_name_1 ?: 'Main Offer' }}</h3>
                        <div class="space-y-4">
                            <div class="money-line money-line-row">
                                <span>{{ __('proposal.first_year_fee') }}</span>
                                <span class="money-line-amounts">
                                    @if(filled($proposal->offer_1_original_price))
                                        <span
                                            class="money-line-original">{{ $toMoney($proposal->offer_1_original_price) }}</span>
                                    @endif
                                    <span class="money-line-value">{{ $toMoney($proposal->offer_1_price) }}</span>
                                </span>
                            </div>
                            @if(filled($proposal->offer_1_renewal_price))
                                <div class="money-line money-line-row">
                                    <span>{{ __('proposal.renewal_fee') }}</span>
                                    <span class="money-line-amounts">
                                        @if(filled($proposal->offer_1_original_renewal_price))
                                            <span
                                                class="money-line-original">{{ $toMoney($proposal->offer_1_original_renewal_price) }}</span>
                                        @endif
                                        <span class="money-line-value">{{ $toMoney($proposal->offer_1_renewal_price) }}</span>
                                    </span>
                                </div>
                                <span class="italic text-[10px] md:text-sm">*{{ __('proposal.renewal_optional_note') }}</span>
                            @endif
                        </div>
                    </div>

                    @if($hasOffer2)
                        <div class="pricing-option-divider">
                            <span class="proposal-kicker document-kicker -mb-4">Option
                                02</span>
                            <h3 class="offer_name">{{ $proposal->offer_name_2 ?: 'Alternative Offer' }}</h3>
                            <div class="space-y-4">
                                @if(filled($proposal->offer_2_price))
                                    <div class="money-line money-line-row">
                                        <span>{{ __('proposal.first_year_fee') }}</span>
                                        <span class="money-line-amounts">
                                            @if(filled($proposal->offer_2_original_price))
                                                <span
                                                    class="money-line-original">{{ $toMoney($proposal->offer_2_original_price) }}</span>
                                            @endif
                                            <span class="money-line-value">{{ $toMoney($proposal->offer_2_price) }}</span>
                                        </span>
                                    </div>
                                @endif
                                @if(filled($proposal->offer_2_renewal_price))
                                    <div class="money-line money-line-row">
                                        <span>{{ __('proposal.renewal_fee') }}</span>
                                        <span class="money-line-amounts">
                                            @if(filled($proposal->offer_2_original_renewal_price))
                                                <span
                                                    class="money-line-original">{{ $toMoney($proposal->offer_2_original_renewal_price) }}</span>
                                            @endif
                                            <span class="money-line-value">{{ $toMoney($proposal->offer_2_renewal_price) }}</span>
                                        </span>
                                    </div>
                                    <span class="italic text-[10px] md:text-sm">*{{ __('proposal.renewal_optional_note') }}</span>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="proposal-alert">
                        <p class="mb-0 text-xs leading-relaxed md:text-inherit">{{ __('proposal.money_back_guarantee_text') }}
                            <br><a
                                class="font-semibold underline decoration-current/60 underline-offset-2 transition hover:text-[#2f5f30]"
                                href="#garansi">{{ __('proposal.money_back_guarantee_terms_link') }}</a>
                        </p>
                    </div>
                </div>
            </section>
        @endif

        @if($hasAddOns)
            <section class="section-row allow-page-break">
                <h2 class="section-label">{{ __('proposal.add_ons_title') }}</h2>
                <div class="section-content">
                    <p>{{ __('proposal.add_ons_description') }}</p>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($addOns as $row)
                                    <tr>
                                        <td>
                                            <b>{{ $row['name'] ?? '-' }}</b>
                                            <br>{{ $row['description'] ?? '-' }}
                                        </td>
                                        <td>{{ $row['price'] ?? 0 }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif

        @if(count($offer1Timeline) || count($offer2Timeline))
            <section id="timeline" class="section-row allow-page-break">
                <h2 class="section-label">Project Timeline</h2>
                <div class="section-content space-y-10">
                    @if(count($offer1Timeline))
                        @php
                            $hasOffer2Timeline = count($offer2Timeline) > 0;
                            $offer1TotalDays = collect($offer1Timeline)->sum(function (array $row): int {
                                $days = $row['activity_days'] ?? 0;

                                return is_numeric($days) ? (int) $days : 0;
                            });
                        @endphp
                        <div>
                            @if($hasOffer2Timeline)
                                <p class="proposal-kicker document-subkicker">
                                    {{ $proposal->offer_name_1 ?: 'Main Offer' }} Timeline
                                </p>
                            @endif
                            <div class="table-wrap">
                                <table class="data-table timeline-table">
                                    <thead>
                                        <tr>
                                            <th>Activity</th>
                                            <th>PIC</th>
                                            <th>Day(s)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($offer1Timeline as $row)
                                            <tr>
                                                <td>{{ $row['activity_name'] ?? '-' }}</td>
                                                <td>{{ $row['activity_pic'] ?? '-' }}</td>
                                                <td>{{ $row['activity_days'] ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="2">Total Days</th>
                                            <th>{{ $offer1TotalDays }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if(count($offer2Timeline))
                        @php
                            $offer2TotalDays = collect($offer2Timeline)->sum(function (array $row): int {
                                $days = $row['activity_days'] ?? 0;

                                return is_numeric($days) ? (int) $days : 0;
                            });
                        @endphp
                        <div>
                            <p class="proposal-kicker document-subkicker">
                                {{ $proposal->offer_name_2 ?: 'Alternative Offer' }} Timeline
                            </p>
                            <div class="table-wrap">
                                <table class="data-table timeline-table">
                                    <thead>
                                        <tr>
                                            <th>Activity</th>
                                            <th>PIC</th>
                                            <th>Day(s)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($offer2Timeline as $row)
                                            <tr>
                                                <td>{{ $row['activity_name'] ?? '-' }}</td>
                                                <td>{{ $row['activity_pic'] ?? '-' }}</td>
                                                <td>{{ $row['activity_days'] ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="2">Total Days</th>
                                            <th>{{ $offer2TotalDays }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        @if($present($paymentHtml))
            <section id="payment" class="section-row allow-page-break">
                <h2 class="section-label">Payment Terms</h2>
                <div class="section-content">
                    {!! $paymentHtml !!}
                </div>
            </section>
        @endif

        @if($present($additionalInfoHtml))
            <section class="section-row allow-page-break">
                <h2 class="section-label">Additional Info</h2>
                <div class="section-content">
                    {!! $additionalInfoHtml !!}
                </div>
            </section>
        @endif

        @if($present($termsConditionHtml))
            <section id="terms-and-conditions" class="section-row allow-page-break">
                <h2 class="section-label">Terms & Conditions</h2>
                <div class="section-content">
                    {!! $termsConditionHtml !!}
                </div>
            </section>
        @endif

        <section class="proposal-endcap print-separator avoid-page-break block">
            <div class="endcap-row-logo">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $company?->brand_name }}" class="h-8 object-contain invert grayscale">
                @endif
            </div>

            <div class="endcap-row-body">
                <div class="endcap-side">
                    @if(filled($company?->address) || count($companyEmails) || count($companyPhones))
                        <p class="endcap-bank">
                            @if(filled($company?->address))
                                {{ $company->address }}<br>
                            @endif
                            @if(count($companyEmails))
                                <span class="endcap-contact-row">
                                    <svg class="endcap-contact-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M4 7h16v10H4V7z" stroke="currentColor" stroke-width="1.6" />
                                        <path d="M4 8l8 6 8-6" stroke="currentColor" stroke-width="1.6" />
                                    </svg>
                                    <span>
                                        @foreach($companyEmails as $email)
                                            <a href="mailto:{{ $email }}"
                                                class="endcap-contact-link">{{ $email }}</a>@if(!$loop->last) | @endif
                                        @endforeach
                                    </span>
                                </span>
                            @endif
                            @if(count($companyPhones))
                                @foreach($companyPhones as $phone)
                                    <span class="endcap-contact-row">
                                        <svg class="endcap-contact-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path
                                                d="M7.5 4h3l1.4 3.6-1.8 1.8a14 14 0 0 0 4.3 4.3l1.8-1.8L20 13.5v3a2 2 0 0 1-2.2 2c-6.4-.7-11.6-5.9-12.3-12.3A2 2 0 0 1 7.5 4z"
                                                stroke="currentColor" stroke-width="1.6" />
                                        </svg>
                                        <span class="endcap-contact-list">

                                            @php
                                                $phoneHref = preg_replace('/[^0-9+]/', '', (string) $phone);
                                            @endphp
                                            <a href="tel:{{ $phoneHref }}" class="endcap-contact-link">{{ $phone }}</a>

                                        </span>
                                    </span>
                                @endforeach
                            @endif
                        </p>
                    @endif

                    @if($present($footerTextHtml))
                        <div class="editorial-prose mt-8">{!! $footerTextHtml !!}</div>
                    @endif
                </div>
                <div class="endcap-main">
                    @if($footerBarcodeUrl)
                        <div class="w-full text-left md:text-right">
                            @if($companyWebsiteUrl)
                                <a href="{{ $companyWebsiteUrl }}/?utm_source=proposal&utm_medium=qr&utm_campaign={{ $proposal->slug }}"
                                    target="_blank" rel="noreferrer">
                                    <img src="{{ $footerBarcodeUrl }}" alt="QR Code"
                                        class="h-24 w-24 rounded-sm bg-white p-1 object-contain md:ml-auto">
                                </a>
                            @else
                                <img src="{{ $footerBarcodeUrl }}" alt="QR Code"
                                    class="h-24 w-24 rounded-sm bg-white p-1 object-contain md:ml-auto">
                            @endif
                            <p class="endcap-label mt-3 mb-0">{{ __('proposal.scan_qr_website') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <p class="endcap-copyright text-xs">&copy; {{ now()->year }} {{ $company?->company_name }}
            </p>

        </section>
    </div>
    @if(! $pdfMode)
        <nav class="floating-doc-nav" aria-label="Proposal Sections">
            <a href="#services">Services</a>
            <a href="#price">Price</a>
            <a href="#timeline">Timeline</a>
            <a href="#payment">Payment</a>
            <a href="#terms-and-conditions">Terms & Conditions</a>
            <a href="{{ route('pdf.proposal', ['slug' => $slug, 'lang' => $locale]) }}">Download PDF</a>
        </nav>

        <details class="floating-doc-flyout">
            <summary>
                <span>Sections</span>
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </summary>
            <nav aria-label="Proposal Sections Mobile">
                <a href="#services" class="js-flyout-link">Services</a>
                <a href="#price" class="js-flyout-link">Price</a>
                <a href="#timeline" class="js-flyout-link">Timeline</a>
                <a href="#payment" class="js-flyout-link">Payment</a>
                <a href="#terms-and-conditions" class="js-flyout-link">Terms & Conditions</a>
                <a href="{{ route('pdf.proposal', ['slug' => $slug, 'lang' => $locale]) }}">Download PDF</a>
            </nav>
        </details>

        <script>
            document.querySelectorAll('.js-flyout-link').forEach((link) => {
                link.addEventListener('click', () => {
                    const flyout = link.closest('.floating-doc-flyout');
                    if (flyout) {
                        flyout.removeAttribute('open');
                    }
                });
            });
        </script>
    @endif
</x-layout>
