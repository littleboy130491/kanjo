<!doctype html>
<html lang="{{ $lang }}">
@php
    $isIndonesian = $lang === 'id';
    $companyLogo = $company?->logo;
    $logoUrl = is_string($companyLogo) && $companyLogo !== ''
        ? (str_starts_with($companyLogo, 'http') ? $companyLogo : asset('storage/' . ltrim($companyLogo, '/')))
        : null;
    $brandName = $company?->brand_name ?: $company?->company_name;
    $websiteUrl = filled($company?->website)
        ? (str_starts_with((string) $company->website, 'http://') || str_starts_with((string) $company->website, 'https://')
            ? (string) $company->website
            : 'https://' . ltrim((string) $company->website, '/'))
        : null;
    $contacts = collect([
        ['label' => 'Email', 'value' => $company?->email_1, 'href' => filled($company?->email_1) ? 'mailto:' . $company->email_1 : null],
        ['label' => $isIndonesian ? 'Telepon' : 'Phone', 'value' => $company?->phone_1, 'href' => filled($company?->phone_1) ? 'tel:' . preg_replace('/[^0-9+]/', '', (string) $company->phone_1) : null],
        ['label' => 'Website', 'value' => $company?->website, 'href' => $websiteUrl],
    ])->filter(fn (array $contact): bool => filled($contact['value']));
@endphp

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $isIndonesian ? 'Proposal Kedaluwarsa' : 'Proposal Expired' }}</title>
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="googlebot" content="noindex, nofollow, noarchive">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Montserrat:wght@200;300;400;500;600&display=swap"
        rel="stylesheet">
    @vite('resources/css/app.css')
</head>

<body class="auth-shell">
    <main class="mx-auto flex min-h-[calc(100vh-3rem)] max-w-[210mm] items-center">
        <section class="auth-panel">
            <div class="auth-inner">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $brandName }}" class="mb-10 h-8 object-contain">
                @elseif (filled($brandName))
                    <p class="auth-label mb-10">{{ $brandName }}</p>
                @endif

                <p class="auth-label">{{ $proposal->document_number }}</p>
                <h1 class="auth-title mt-3">
                    {{ $isIndonesian ? 'Proposal ini telah kedaluwarsa.' : 'This proposal has expired.' }}
                </h1>
                <p class="mt-5 max-w-xl text-[14px] leading-[1.55] text-neutral-600 md:text-[16px] md:leading-normal">
                    {{ $isIndonesian
                        ? 'Silakan hubungi kami apabila membutuhkan bantuan atau ingin memperbarui penawaran ini.'
                        : 'Please contact us if you need any help or would like an updated offer.' }}
                </p>

                @if ($contacts->isNotEmpty())
                    <dl class="mt-10 space-y-5 border-t border-sky-100 pt-8">
                        @foreach ($contacts as $contact)
                            <div>
                                <dt class="auth-label">{{ $contact['label'] }}</dt>
                                <dd class="text-sm text-neutral-800">
                                    @if ($contact['href'])
                                        <a href="{{ $contact['href'] }}" class="underline decoration-sky-200 underline-offset-4 hover:decoration-slate-400"
                                            @if ($contact['label'] === 'Website') target="_blank" rel="noopener" @endif>{{ $contact['value'] }}</a>
                                    @else
                                        {{ $contact['value'] }}
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </div>
        </section>
    </main>
</body>

</html>
