<?php

namespace App\Services\DocumentApi;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Proposal;
use App\Models\Service;
use App\Models\Spk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class DocumentCatalog
{
    public static function limit(Request $request): int
    {
        $limit = (int) $request->query('limit', 50);

        return max(1, min(100, $limit));
    }

    public static function decimal(Model $model, string $key): ?float
    {
        $value = $model->getAttributes()[$key] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * @param  array<int, string>  $columns
     */
    public static function applySearch(Builder $query, string $term, array $columns): void
    {
        $term = trim($term);

        if ($term === '' || $columns === []) {
            return;
        }

        $query->where(function (Builder $builder) use ($term, $columns): void {
            foreach ($columns as $index => $column) {
                if ($index === 0) {
                    $builder->where($column, 'like', "%{$term}%");

                    continue;
                }

                $builder->orWhere($column, 'like', "%{$term}%");
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public static function client(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'company' => $client->company,
            'email' => $client->email,
            'phone' => $client->phone,
            'address' => $client->address,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function proposal(Proposal $proposal): array
    {
        $slug = (string) $proposal->slug;

        return [
            'type' => 'proposal',
            'id' => $proposal->id,
            'document_number' => $proposal->document_number,
            'status' => $proposal->status?->value ?? $proposal->status,
            'client_id' => $proposal->client_id,
            'company_id' => $proposal->company_id,
            'client_company' => $proposal->client_company,
            'client_name' => $proposal->client_name,
            'client_email' => $proposal->client_email,
            'client_phone' => $proposal->client_phone,
            'issue_date' => optional($proposal->issue_date)?->toDateString(),
            'valid_until' => optional($proposal->valid_until)?->toDateString(),
            'currency' => $proposal->currency,
            'offer_name_1' => $proposal->offer_name_1,
            'offer_1_price' => self::decimal($proposal, 'offer_1_price'),
            'offer_1_renewal_price' => self::decimal($proposal, 'offer_1_renewal_price'),
            'offer_name_2' => $proposal->offer_name_2,
            'offer_2_price' => self::decimal($proposal, 'offer_2_price'),
            'invoices_count' => $proposal->invoices_count ?? $proposal->invoices()->count(),
            'public_url' => $slug !== '' ? route('proposal.show', ['slug' => $slug]) : null,
            'pdf_url' => $slug !== '' ? route('pdf.proposal', ['slug' => $slug]) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function invoice(Invoice $invoice): array
    {
        $slug = (string) $invoice->slug;

        return [
            'type' => 'invoice',
            'id' => $invoice->id,
            'document_number' => $invoice->document_number,
            'status' => $invoice->status?->value ?? $invoice->status,
            'payment_status' => $invoice->payment_status?->value ?? $invoice->payment_status,
            'client_id' => $invoice->client_id,
            'company_id' => $invoice->company_id,
            'proposal_id' => $invoice->proposal_id,
            'service_id' => $invoice->service_id,
            'client_company' => $invoice->client_company,
            'client_name' => $invoice->client_name,
            'client_email' => $invoice->client_email,
            'client_phone' => $invoice->client_phone,
            'issue_date' => optional($invoice->issue_date)?->toDateString(),
            'due_date' => optional($invoice->due_date)?->toDateString(),
            'currency' => $invoice->currency,
            'subtotal' => self::decimal($invoice, 'subtotal'),
            'tax_rate' => self::decimal($invoice, 'tax_rate'),
            'tax_amount' => self::decimal($invoice, 'tax_amount'),
            'total' => self::decimal($invoice, 'total'),
            'public_url' => $slug !== '' ? route('invoice.show', ['slug' => $slug]) : null,
            'pdf_url' => $slug !== '' ? route('pdf.invoice', ['slug' => $slug]) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function spk(Spk $spk): array
    {
        $slug = (string) $spk->slug;

        return [
            'type' => 'spk',
            'id' => $spk->id,
            'document_number' => $spk->document_number,
            'status' => $spk->status?->value ?? $spk->status,
            'client_id' => $spk->client_id,
            'company_id' => $spk->company_id,
            'proposal_id' => $spk->proposal_id,
            'client_company' => $spk->client_company,
            'client_pic_name' => $spk->client_pic_name,
            'spk_date' => optional($spk->spk_date)?->toDateString(),
            'subject' => $spk->getTranslation('subject', config('app.locale', 'en')) ?: $spk->getTranslation('subject', 'id'),
            'public_url' => $slug !== '' ? route('spk.show', ['slug' => $slug]) : null,
            'pdf_url' => $slug !== '' ? route('pdf.spk', ['slug' => $slug]) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function service(Service $service): array
    {
        return [
            'type' => 'service',
            'id' => $service->id,
            'name' => $service->name,
            'domain' => $service->domain,
            'start_date' => $service->start_date,
            'renewal_date' => $service->renewal_date,
            'price' => self::decimal($service, 'price'),
            'currency' => $service->currency,
            'status' => $service->status?->value ?? $service->status,
            'client_id' => $service->client_id,
            'invoices_count' => $service->invoices_count ?? $service->invoices()->count(),
        ];
    }
}
