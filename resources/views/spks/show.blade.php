@php
    $company = $spk->company;
    $pdfMode = (bool) ($pdf ?? false);
    $editUrl = auth()->check()
        ? \App\Filament\Admin\Resources\Spks\SpkResource::getUrl('edit', ['record' => $spk])
        : null;
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
    $titleHtml = $asHtml($spk->title);
    $contentHtml = $asHtml($spk->content);
    $clientAddressHtml = $lineBreakText($spk->client_address);
    $companyAddressHtml = $lineBreakText($spk->company_address);
@endphp

<x-layout :locale="$locale" :title="$spk->document_number" :company="$company" :pdf-mode="$pdfMode" :slug="$slug"
    :edit-url="$editUrl" :activate-translation="true" :is-draft="$spk->status->value === 'draft'" lang-route="spk.show" pdf-route="pdf.spk">
    <div class="document-frame document-view document-shell spk-document">
        <section class="document-section-pad spk-cover avoid-page-break">
            <div class="spk-title document-richtext">
                {!! $titleHtml !!}
            </div>
            <p class="spk-number">Nomor SPK {{ $spk->document_number }}</p>
            <div class="spk-meta-grid">
                <div>
                    <span class="document-accent document-kicker">PIHAK PERTAMA</span>
                    <p class="spk-party-name">{{ $spk->client_company }}</p>
                    <p>{{ $spk->client_pic_name }}</p>
                    @if(filled($spk->client_pic_role))
                        <p>{{ $spk->client_pic_role }}</p>
                    @endif
                    @if($clientAddressHtml !== '')
                        <p class="spk-address">{!! $clientAddressHtml !!}</p>
                    @endif
                </div>
                <div>
                    <span class="document-accent document-kicker">PIHAK KEDUA</span>
                    <p class="spk-party-name">{{ $spk->company_name }}</p>
                    <p>{{ $spk->company_pic_name }}</p>
                    @if(filled($spk->company_pic_role))
                        <p>{{ $spk->company_pic_role }}</p>
                    @endif
                    @if($companyAddressHtml !== '')
                        <p class="spk-address">{!! $companyAddressHtml !!}</p>
                    @endif
                </div>
            </div>
        </section>

        <section class="document-section-pad spk-content-section">
            <div class="document-richtext spk-content">
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
