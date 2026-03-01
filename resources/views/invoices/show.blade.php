@php
    $company = $invoice->company;
    $companyLogo = $company?->logo;
    $toMoney = fn ($value) => $invoice->currency . ' ' . number_format((float) ($value ?? 0), 2);
    $badgeColor = match($invoice->payment_status->value) {
        'paid' => 'bg-green-100 text-green-700',
        'partially_paid' => 'bg-blue-100 text-blue-700',
        'overdue' => 'bg-red-100 text-red-700',
        default => 'bg-yellow-100 text-yellow-700',
    };
    $logoUrl = is_string($companyLogo) && str_starts_with($companyLogo, 'http') ? $companyLogo : null;
    $pdfMode = (bool) ($pdf ?? false);
@endphp
<x-layout
    :locale="$locale"
    :title="$invoice->document_number"
    :company="$company"
    :pdf-mode="$pdfMode"
    :slug="$slug"
    lang-route="invoice.show"
    pdf-route="pdf.invoice"
>

    <section class="border-b pb-4">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $company?->brand_name }}" class="h-12 w-12 object-contain">
                @endif
                <div>
                    <h1 class="text-2xl font-bold brand-title">Invoice {{ $invoice->document_number }}</h1>
                    <p class="text-sm text-slate-500">{{ $company?->brand_name ?? $company?->company_name }}</p>
                </div>
            </div>
            <div class="text-right text-sm">
                <p>Issue: {{ optional($invoice->issue_date)->format('d M Y') }}</p>
                <p>Due: {{ optional($invoice->due_date)->format('d M Y') }}</p>
                <span class="inline-block rounded-full px-3 py-1 text-xs font-semibold {{ $badgeColor }}">{{ $invoice->payment_status->getLabel() }}</span>
            </div>
        </div>
    </section>

    <section><h2 class="mb-2 font-semibold">Client Info</h2><p>{{ $invoice->client_company }} — {{ $invoice->client_name }}</p><p>{{ $invoice->client_email }} | {{ $invoice->client_phone }}</p></section>

    <section>
        <h2 class="mb-2 font-semibold">Items</h2>
        <table class="w-full border-collapse text-sm">
            <thead><tr class="bg-slate-50"><th class="border p-2 text-left">Title</th><th class="border p-2 text-left">Description</th><th class="border p-2 text-right">Price</th></tr></thead>
            <tbody>
            @foreach(($invoice->items ?? []) as $item)
                <tr>
                    <td class="border p-2">{{ $item['title'] ?? '-' }}</td>
                    <td class="border p-2">{{ $item['description'] ?? '-' }}</td>
                    <td class="border p-2 text-right">{{ $toMoney($item['price'] ?? 0) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>

    <section class="rounded border p-4">
        <h2 class="mb-2 font-semibold">Financial Summary</h2>
        <p>Subtotal: {{ $toMoney($invoice->subtotal) }}</p>
        <p>Tax ({{ $invoice->tax_rate }}%): {{ $toMoney($invoice->tax_amount) }}</p>
        <p class="font-semibold">Total: {{ $toMoney($invoice->total) }}</p>
    </section>

    @if($invoice->paid_amount || $invoice->payment_method || $invoice->paid_at)
    <section class="rounded border p-4">
        <h2 class="mb-2 font-semibold">Payment Info</h2>
        <p>Paid amount: {{ $toMoney($invoice->paid_amount) }}</p>
        <p>Payment method: {{ $invoice->payment_method ?: '-' }}</p>
        <p>Paid at: {{ optional($invoice->paid_at)->format('d M Y H:i') ?: '-' }}</p>
    </section>
    @endif

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
</x-layout>
