@php
    $company = $proposal->company;
    $companyLogo = $company?->logo;
    $pdfMode = (bool) ($pdf ?? false);
    $activateTranslation = (bool) $proposal->activate_translation;
    $pdfRouteParameters = ['slug' => $slug];

    if ($activateTranslation) {
        $pdfRouteParameters['lang'] = $locale;
    }

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
    $editUrl = auth()->check()
        ? \App\Filament\Admin\Resources\Proposals\ProposalResource::getUrl('edit', ['record' => $proposal])
        : null;
    $companyWebsiteUrl = filled($company?->website)
        ? (str_starts_with((string) $company->website, 'http://') || str_starts_with((string) $company->website, 'https://')
            ? (string) $company->website
            : 'https://' . ltrim((string) $company->website, '/'))
        : null;
    $portfolioArchiveUrl = 'https://imajiner.id/portfolio/?utm_source=proposal&utm_medium=link&utm_campaign=' . urlencode((string) $proposal->slug);
    $logoUrl = is_string($companyLogo) && $companyLogo !== ''
        ? (str_starts_with($companyLogo, 'http') ? $companyLogo : asset('storage/' . ltrim($companyLogo, '/')))
        : $fallbackLogoUrl;

    $htmlHasContent = function (mixed $value): bool {
        if (! is_string($value)) {
            return filled($value);
        }

        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\x{00A0}\x{200B}-\x{200D}\x{FEFF}]/u', '', $text) ?? $text;

        return trim($text) !== '';
    };

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

    $lineBreakText = function (mixed $value): string {
        if (! is_string($value) || trim($value) === '') {
            return '';
        }

        return collect(preg_split('/(?:<br\s*\/?>|\R)/i', $value) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter(fn (string $line): bool => $line !== '')
            ->map(fn (string $line): string => e($line))
            ->implode('<br>');
    };

    $asHtmlWithLocaleFallback = function (mixed $value) use ($locale, $htmlHasContent): string {
        if (! is_array($value)) {
            return is_string($value) ? trim($value) : '';
        }

        $localized = $value[$locale] ?? null;

        if (is_string($localized) && $htmlHasContent($localized)) {
            return trim($localized);
        }

        $fallbackLocales = array_unique(array_filter([
            config('app.locale', 'id'),
            config('app.fallback_locale', 'en'),
            ...config('translatable.locales', ['id', 'en']),
        ]));

        foreach ($fallbackLocales as $fallbackLocale) {
            if ($fallbackLocale === $locale) {
                continue;
            }

            $fallback = $value[$fallbackLocale] ?? null;

            if (is_string($fallback) && $htmlHasContent($fallback)) {
                return trim($fallback);
            }
        }

        foreach ($value as $fallback) {
            if (is_string($fallback) && $htmlHasContent($fallback)) {
                return trim($fallback);
            }
        }

        return is_string($localized) ? trim($localized) : '';
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

    $present = function (mixed $value) use ($htmlHasContent): bool {
        if (is_array($value)) {
            return count($value) > 0;
        }

        return $htmlHasContent($value);
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
    $clientAddressHtml = $lineBreakText($proposal->client_address);
    $faqHtml = $asHtmlWithLocaleFallback($proposal->faq);
    $ourProcessHtml = $asHtmlWithLocaleFallback($proposal->our_process);
    $aboutUsHtml = $asHtmlWithLocaleFallback($proposal->about_us);

    if (! $present($faqHtml) && blank($proposal->getRawOriginal('faq'))) {
        $proposalContentDefault = \App\Models\ProposalContentDefault::defaultPack();
        $defaultTranslations = $proposalContentDefault?->getTranslations('value') ?? [];
        $defaultFaq = collect(config('translatable.locales', ['id', 'en']))
            ->mapWithKeys(fn (string $defaultLocale): array => [
                $defaultLocale => data_get($defaultTranslations, "{$defaultLocale}.faq"),
            ])
            ->all();

        $faqHtml = $asHtmlWithLocaleFallback($defaultFaq);
    }
    $footerTextHtml = trim((string) ($company?->getTranslation('footer_text', $locale, false) ?? ''));

    $offer1Timeline = $asRows($proposal->offer_1_project_timeline);
    $offer2Timeline = $asRows($proposal->offer_2_project_timeline);
    $addOns = $asRows($proposal->add_on);
    $hasOffer2 = filled($proposal->offer_name_2) || filled($proposal->offer_2_price) || filled($proposal->offer_2_renewal_price);

    $videoTestimonials = collect($proposal->video_testimonials ?? [])
        ->filter(fn (mixed $row): bool => is_array($row) && filled($row['url'] ?? null))
        ->values()
        ->all();

    $clientLogos = collect($proposal->client_logos ?? [])
        ->filter(fn (mixed $row): bool => is_array($row) && filled($row['url'] ?? null))
        ->values()
        ->all();

    $videoEmbedUrl = function (string $url): ?string {
        $url = trim($url);

        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches) === 1) {
            return 'https://www.youtube-nocookie.com/embed/' . $matches[1];
        }

        if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $matches) === 1) {
            return 'https://player.vimeo.com/video/' . $matches[1];
        }

        return null;
    };

    $hasTimeline = count($offer1Timeline) > 0 || count($offer2Timeline) > 0;
    $hasClientLogos = count($clientLogos) > 0;
    $hasVideoTestimonials = count($videoTestimonials) > 0 && ! $pdfMode;
    $googleMapsEmbedSrc = ! $pdfMode ? $company?->googleMapsEmbedSrc() : null;
    $googleMapsLink = ! $pdfMode ? $company?->googleMapsLink() : null;
    $hasAboutUsSection = $present($aboutUsHtml);

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
    :edit-url="$editUrl" :activate-translation="$activateTranslation" :is-draft="$proposal->status->value === 'draft'"
    :full-width="$proposalV2 ?? false"
    :lang-route="($proposalV2 ?? false) ? 'proposal-v2.show' : 'proposal.show'" pdf-route="pdf.proposal">
    <div @class([
        'document-view',
        'document-frame document-shell' => ! ($proposalV2 ?? false),
        'proposal-v2' => $proposalV2 ?? false,
    ])>
        <section class="document-cover document-cover-pad avoid-page-break">
            <div class="document-cover-header">
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
                <span class="document-accent document-kicker mb-6">Project
                    Proposal</span>
                <h1 class="cover-title document-serif">
                    Website<br>
                    <span class="cover-subtitle">Design & Development</span>
                </h1>
            </div>

            @if(filled($proposal->client_company))
                <div class="flex flex-col items-end justify-between gap-8 pt-8 md:flex-row">
                    <div>
                        <p class="document-kicker text-neutral-400">Prepared For</p>
                        <p class="text-xl md:text-2xl text-neutral-900 font-bold">{{ $proposal->client_company }}</p>
                        @if($clientAddressHtml !== '')
                            <p class="mt-3 text-sm leading-relaxed text-neutral-500">{!! $clientAddressHtml !!}</p>
                        @endif
                    </div>
                </div>
            @endif
        </section>

        @if($proposalV2 ?? false)
            <div class="proposal-v2-body document-frame document-shell">
        @endif

        @if($present($briefHtml) || $present($extraContentBriefHtml))
            <section class="section-row proposal-introduction allow-page-break">
                <h2 class="section-label">Dear {{ $proposal->client_name }},</h2>
                <div class="section-content">
                    @if($present($briefHtml))
                        {!! $briefHtml !!}
                    @endif
                    @if($present($extraContentBriefHtml))
                        <div class="document-richtext mt-6">
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
                        <article class="portfolio-card group cursor-pointer border-0">
                            <a style="text-decoration: none !important;" href="{{ $portfolio->url_link }}" target="_blank" rel="nofollow noopener noreferrer">
                                <div class="mb-4 aspect-[4/3] overflow-hidden bg-neutral-100">
                                    @if($portfolio->portfolio_image_url)
                                        <img src="{{ $portfolio->portfolio_image_url }}" alt="{{ $portfolio->name }}"
                                            class="h-full w-full object-cover object-top transition-transform duration-500 ease-out group-hover:scale-105">
                                    @endif
                                </div>
                                <p class="portfolio-card-title text-sm text-neutral-900 md:text-lg">{{ $portfolio->name }}</p>
                                @if(filled($portfolio->url_link))
                                    <span class="portfolio-card-link document-accent mt-2 inline-block text-[10px] uppercase tracking-[0.2em]">
                                        View Live Site ->
                                    </span>
                                @endif
                            </a>
                        </article>
                    @endforeach
                </div>
                <div class="section-content mt-8">
                    <p>
                        {{ __('proposal.portfolio_archive_intro') }}
                        <a href="{{ $portfolioArchiveUrl }}" target="_blank" rel="nofollow noopener noreferrer"
                            class="underline underline-offset-4">{{ __('proposal.portfolio_archive_link') }}</a>.
                    </p>
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
                            <span class="document-accent document-kicker -mb-4">Option
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
                            <span class="document-accent document-kicker -mb-4">Option
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

                    <div class="document-alert">
                        <p class="mb-0 text-xs leading-relaxed md:text-inherit">{{ __('proposal.money_back_guarantee_text') }} {{ __('proposal.money_back_guarantee_terms_link') }}
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

        @if($hasTimeline)
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
                                <p class="document-accent document-subkicker">
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
                            <p class="document-accent document-subkicker">
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

        @if($present($ourProcessHtml))
            <section id="our-process" class="section-row allow-page-break">
                <h2 class="section-label">Our Process</h2>
                <div class="section-content">
                    {!! $ourProcessHtml !!}
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

        @if($present($faqHtml))
            <section id="faq" class="section-row allow-page-break">
                <h2 class="section-label">Frequently Asked Questions</h2>
                <div class="section-content">
                    {!! $faqHtml !!}
                </div>
            </section>
        @endif

        @if($hasAboutUsSection)
            <section id="about-us" class="section-row allow-page-break">
                <h2 class="section-label">About Us</h2>
                <div class="section-content about-us-stack">
                    @if($present($aboutUsHtml))
                        <div class="document-richtext about-us-copy">
                            {!! $aboutUsHtml !!}
                        </div>
                    @endif

                    @if(filled($googleMapsEmbedSrc))
                        <div class="about-us-subblock">
                            <p class="document-accent document-subkicker">Our Location</p>
                            <div class="company-map-wrap">
                                <iframe
                                    src="{{ $googleMapsEmbedSrc }}"
                                    title="{{ $company?->brand_name }} location map"
                                    loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"
                                    allowfullscreen
                                ></iframe>
                            </div>
                            @if(filled($googleMapsLink))
                                <p class="mt-4">
                                    <a href="{{ $googleMapsLink }}" target="_blank" rel="noopener noreferrer" class="underline">
                                        Open in Google Maps
                                    </a>
                                </p>
                            @endif
                        </div>
                    @endif

                    @if($hasClientLogos)
                        <div class="about-us-subblock">
                            <p class="document-accent document-subkicker">Trusted by</p>
                            <div class="client-logos-grid">
                                @foreach($clientLogos as $clientLogo)
                                    @php
                                        $clientLogoUrl = trim((string) ($clientLogo['url'] ?? ''));
                                    @endphp
                                    <figure class="client-logo-card">
                                        <img src="{{ $clientLogoUrl }}" alt="Client logo" class="client-logo-image"@unless($pdfMode) loading="lazy"@endunless>
                                    </figure>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($hasVideoTestimonials)
                        <div class="about-us-subblock">
                            <div class="video-testimonials-grid">
                                @foreach($videoTestimonials as $testimonial)
                                    @php
                                        $videoUrl = trim((string) ($testimonial['url'] ?? ''));
                                        $embedUrl = $videoEmbedUrl($videoUrl);
                                    @endphp
                                    <article class="video-testimonial-card">
                                        @if($embedUrl)
                                            <div class="video-testimonial-frame">
                                                <iframe
                                                    src="{{ $embedUrl }}"
                                                    title="Client video testimonial"
                                                    loading="lazy"
                                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                                    allowfullscreen
                                                ></iframe>
                                            </div>
                                        @else
                                            <a href="{{ $videoUrl }}" target="_blank" rel="noopener noreferrer"
                                                class="video-testimonial-link">
                                                <span class="video-testimonial-link-icon" aria-hidden="true">
                                                    <svg viewBox="0 0 24 24" fill="none">
                                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5" />
                                                        <path d="M10 8.5v7l6-3.5-6-3.5z" fill="currentColor" />
                                                    </svg>
                                                </span>
                                                <span class="video-testimonial-link-label">Watch testimonial</span>
                                                <span class="video-testimonial-link-url">{{ $videoUrl }}</span>
                                            </a>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        @if($proposalV2 ?? false)
            </div>
        @endif

        <section class="document-endcap print-separator avoid-page-break block">
            @if($proposalV2 ?? false)
                <div class="proposal-v2-footer-inner">
            @endif

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
                                {!! $company->address !!}
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
                        <div class="document-richtext editorial-prose mt-8">{!! $footerTextHtml !!}</div>
                    @endif
                </div>
                <div class="endcap-main">
                    @if($footerBarcodeUrl)
                        <div class="w-full text-left md:text-right">
                            @if($companyWebsiteUrl)
                                <a href="{{ $companyWebsiteUrl }}/?utm_source=proposal&utm_medium=qr&utm_campaign={{ $proposal->slug }}"
                                    target="_blank" rel="nofollow noopener noreferrer">
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

            @if($proposalV2 ?? false)
                </div>
            @endif
        </section>
    </div>
    @if(! $pdfMode)
        <nav class="floating-doc-nav" aria-label="Proposal Sections">
            <a href="#services">Services</a>
            <a href="#price">Price</a>
            <a href="#timeline">Timeline</a>
            @if($present($ourProcessHtml))
                <a href="#our-process">Our Process</a>
            @endif
            <a href="#payment">Payment</a>
            <a href="#terms-and-conditions">Terms & Conditions</a>
            @if($present($faqHtml))
                <a href="#faq">FAQ</a>
            @endif
            @if($hasAboutUsSection)
                <a href="#about-us">About Us</a>
            @endif
            <a href="{{ route('pdf.proposal', $pdfRouteParameters) }}">Download PDF</a>
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
                @if($present($ourProcessHtml))
                    <a href="#our-process" class="js-flyout-link">Our Process</a>
                @endif
                <a href="#payment" class="js-flyout-link">Payment</a>
                <a href="#terms-and-conditions" class="js-flyout-link">Terms & Conditions</a>
                @if($present($faqHtml))
                    <a href="#faq" class="js-flyout-link">FAQ</a>
                @endif
                @if($hasAboutUsSection)
                    <a href="#about-us" class="js-flyout-link">About Us</a>
                @endif
                <a href="{{ route('pdf.proposal', $pdfRouteParameters) }}">Download PDF</a>
            </nav>
        </details>

        <script>
            @if($proposalV2 ?? false)
                const proposalV2Body = document.querySelector('.proposal-v2-body');
                const proposalV2Navigation = document.querySelectorAll('.floating-doc-nav, .floating-doc-flyout');

                if (proposalV2Body && 'IntersectionObserver' in window) {
                    const proposalV2BodyObserver = new IntersectionObserver((entries) => {
                        const isBodyVisible = entries.some((entry) => entry.isIntersecting);

                        proposalV2Navigation.forEach((navigation) => {
                            navigation.classList.toggle('proposal-nav-visible', isBodyVisible);
                        });
                    });

                    proposalV2BodyObserver.observe(proposalV2Body);
                } else {
                    proposalV2Navigation.forEach((navigation) => {
                        navigation.classList.add('proposal-nav-visible');
                    });
                }
            @endif

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
