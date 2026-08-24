<?php

namespace App\Services\DocumentApi;

use App\Enums\PaymentStatus;
use App\Enums\ServiceStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Proposal;
use App\Models\Service;
use App\Models\Spk;
use App\Services\SpkTemplateRenderer;
use Illuminate\Support\Facades\DB;

class DocumentUpdater
{
    public function __construct(
        private readonly ContentResolver $content,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function client(Client $client, array $payload): array
    {
        $dryRun = (bool) ($payload['dry_run'] ?? false);
        $attributes = array_intersect_key($payload, array_flip(['name', 'company', 'email', 'phone', 'address']));

        if (array_key_exists('notes', $payload) && is_array($payload['notes'])) {
            $attributes['notes'] = $payload['notes'];
        }

        if ($dryRun) {
            return $this->dryRun('client', $client->id, $attributes);
        }

        $client->fill($attributes);
        $client->save();

        return ['data' => DocumentCatalog::client($client)];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function service(Service $service, array $payload): array
    {
        $dryRun = (bool) ($payload['dry_run'] ?? false);
        $attributes = array_intersect_key($payload, array_flip([
            'name', 'domain', 'start_date', 'renewal_date', 'currency', 'client_id',
        ]));

        if (array_key_exists('status', $payload)) {
            $attributes['status'] = ServiceStatus::from((string) $payload['status']);
        }

        if (array_key_exists('notes', $payload) && is_array($payload['notes'])) {
            $attributes['notes'] = $payload['notes'];
        }

        if (array_key_exists('status', $attributes) && $attributes['status'] instanceof ServiceStatus) {
            $attributes['status'] = $attributes['status']->value;
        }

        if (array_key_exists('price', $payload)) {
            $attributes['price'] = $payload['price'];
        }

        if ($dryRun) {
            return $this->dryRun('service', $service->id, $attributes);
        }

        $attributes['updated_at'] = now();
        DB::table('services')->where('id', $service->id)->update($attributes);
        $service = Service::query()->withCount('invoices')->findOrFail($service->id);

        return ['data' => DocumentCatalog::service($service)];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function company(Company $company, array $payload): array
    {
        $dryRun = (bool) ($payload['dry_run'] ?? false);
        $attributes = array_intersect_key($payload, array_flip([
            'company_name', 'brand_name', 'address', 'email_1', 'email_2',
            'phone_1', 'phone_2', 'tax_id', 'website', 'google_maps_embed_url',
            'default_currency', 'color_primary', 'color_secondary',
        ]));

        if (array_key_exists('bank', $payload) && is_array($payload['bank'])) {
            $attributes['bank'] = $payload['bank'];
        }

        if (array_key_exists('pic', $payload) && is_array($payload['pic'])) {
            $attributes['pic'] = $payload['pic'];
        }

        if (array_key_exists('footer_text', $payload) && is_array($payload['footer_text'])) {
            $attributes['footer_text'] = $payload['footer_text'];
        }

        if ($dryRun) {
            return $this->dryRun('company', $company->id, $attributes);
        }

        $company->fill($attributes);
        $company->save();

        return ['data' => $this->companyPayload($company->refresh())];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function proposal(Proposal $proposal, array $payload): array
    {
        $dryRun = (bool) ($payload['dry_run'] ?? false);
        $author = DocumentApiAuthor::user();

        if ($dryRun) {
            return $this->dryRun('proposal', $proposal->id, $this->previewKeys($payload));
        }

        return DB::transaction(function () use ($proposal, $payload, $author): array {
            $updates = ['updated_by' => $author->id, 'updated_at' => now()];
            $this->mergeDocumentMeta($updates, $payload);
            $this->mergeSnapshot($updates, $payload, [
                'client_company' => 'company',
                'client_name' => 'name',
                'client_email' => 'email',
                'client_phone' => 'phone',
                'client_address' => 'address',
            ]);
            $this->mergeScalars($updates, $payload, [
                'currency', 'offer_name_1', 'offer_name_2', 'company_id', 'client_id',
            ]);
            $this->mergeScalars($updates, $payload, [
                'tax_rate', 'offer_1_price', 'offer_1_original_price', 'offer_1_renewal_price',
                'offer_1_original_renewal_price', 'offer_2_price', 'offer_2_original_price',
                'offer_2_renewal_price', 'offer_2_original_renewal_price',
            ]);

            if (array_key_exists('activate_translation', $payload)) {
                $updates['activate_translation'] = (bool) $payload['activate_translation'];
            }

            if (is_array($payload['content'] ?? null)) {
                foreach ($payload['content'] as $field => $spec) {
                    if (! is_string($field) || ! is_array($spec)) {
                        continue;
                    }

                    $value = $this->content->resolveProposalField($field, $spec, $payload);
                    $updates[$field] = json_encode($value);
                }
            }

            DB::table('proposals')->where('id', $proposal->id)->update($updates);

            $saved = Proposal::query()->withCount('invoices')->findOrFail($proposal->id);

            return ['data' => DocumentCatalog::proposal($saved)];
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function invoice(Invoice $invoice, array $payload): array
    {
        $dryRun = (bool) ($payload['dry_run'] ?? false);
        $author = DocumentApiAuthor::user();

        if ($dryRun) {
            return $this->dryRun('invoice', $invoice->id, $this->previewKeys($payload));
        }

        return DB::transaction(function () use ($invoice, $payload, $author): array {
            $updates = ['updated_by' => $author->id, 'updated_at' => now()];
            $this->mergeDocumentMeta($updates, $payload, 'issue_date', ['due_date']);
            $this->mergeSnapshot($updates, $payload, [
                'client_company' => 'company',
                'client_name' => 'name',
                'client_email' => 'email',
                'client_phone' => 'phone',
                'client_address' => 'address',
            ]);
            $this->mergeScalars($updates, $payload, [
                'currency', 'company_id', 'client_id', 'proposal_id', 'service_id', 'tax_rate',
            ]);

            if (array_key_exists('activate_translation', $payload)) {
                $updates['activate_translation'] = (bool) $payload['activate_translation'];
            }

            if (array_key_exists('payment_status', $payload)) {
                $updates['payment_status'] = (string) $payload['payment_status'];

                if ($payload['payment_status'] === PaymentStatus::PAID->value && blank($invoice->getAttributes()['paid_at'] ?? null)) {
                    $updates['paid_at'] = now();
                }
            }

            $items = array_key_exists('items', $payload)
                ? $this->content->invoiceItems($payload['items'])
                : null;

            if ($items !== null) {
                $updates['items'] = json_encode($items);
            }

            $info = data_get($payload, 'content.additional_info');

            if (is_array($info) && isset($info['mode'])) {
                $updates['additional_info'] = json_encode(
                    $info['mode'] === 'override'
                        ? $this->content->overrideRichText($info['value'] ?? null)
                        : $this->content->emptyTranslations(),
                );
            }

            if ($items !== null || array_key_exists('tax_rate', $payload)) {
                $itemRows = $items['en'] ?? $items['id'] ?? [];
                if ($items === null) {
                    $existing = $invoice->getAttributes()['items'] ?? '[]';
                    $decoded = is_string($existing) ? json_decode($existing, true) : $existing;
                    $itemRows = is_array($decoded) ? ($decoded['en'] ?? $decoded['id'] ?? $decoded) : [];
                }
                $subtotal = 0.0;
                foreach (is_array($itemRows) ? $itemRows : [] as $row) {
                    $subtotal += (float) data_get($row, 'price', 0);
                }
                $taxRate = (float) ($updates['tax_rate'] ?? $invoice->getAttributes()['tax_rate'] ?? 0);
                $taxAmount = round($subtotal * ($taxRate / 100), 2);
                $updates['subtotal'] = round($subtotal, 2);
                $updates['tax_amount'] = $taxAmount;
                $updates['total'] = round($subtotal + $taxAmount, 2);
            }

            DB::table('invoices')->where('id', $invoice->id)->update($updates);

            return ['data' => DocumentCatalog::invoice(Invoice::query()->findOrFail($invoice->id))];
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function spk(Spk $spk, array $payload): array
    {
        $dryRun = (bool) ($payload['dry_run'] ?? false);
        $author = DocumentApiAuthor::user();

        if ($dryRun) {
            return $this->dryRun('spk', $spk->id, $this->previewKeys($payload));
        }

        return DB::transaction(function () use ($spk, $payload, $author): array {
            $updates = ['updated_by' => $author->id, 'updated_at' => now()];
            $this->mergeDocumentMeta($updates, $payload, 'spk_date');
            $this->mergeSnapshot($updates, $payload, [
                'client_company' => 'company',
                'client_pic_name' => 'name',
                'client_pic_role' => 'pic_role',
                'client_address' => 'address',
            ]);
            $this->mergeScalars($updates, $payload, [
                'company_id', 'client_id', 'proposal_id',
                'company_name', 'company_pic_name', 'company_pic_role', 'company_address',
            ]);

            $content = is_array($payload['content'] ?? null) ? $payload['content'] : [];
            $this->mergeSpkContent($updates, $spk, $content, $payload);

            DB::table('spks')->where('id', $spk->id)->update($updates);

            return ['data' => DocumentCatalog::spk(Spk::query()->findOrFail($spk->id))];
        });
    }

    /**
     * @param  array<string, mixed>  $updates
     * @param  array<string, mixed>  $content
     * @param  array<string, mixed>  $payload
     */
    private function mergeSpkContent(array &$updates, Spk $source, array $content, array $payload): void
    {
        if ($content === []) {
            return;
        }

        $original = [];

        foreach (SpkContentCatalog::FIELD_KEYS as $field) {
            $original[$field] = $source->getTranslations($field);
        }

        $rendered = $source;

        $needsDefaults = collect($content)
            ->contains(fn (mixed $spec): bool => is_array($spec) && ($spec['mode'] ?? null) === 'default');

        if ($needsDefaults) {
            $rendered = $source->replicate();
            $offerIndex = (int) ($payload['offer'] ?? $payload['offer_index'] ?? 1);
            SpkTemplateRenderer::renderDefaultsForRecord($rendered, $source->proposal, $offerIndex);
        }

        foreach (SpkContentCatalog::FIELD_KEYS as $field) {
            if (! isset($content[$field]) || ! is_array($content[$field])) {
                continue;
            }

            $mode = (string) ($content[$field]['mode'] ?? '');
            $translations = match ($mode) {
                'empty' => $this->content->emptyTranslations(),
                'override' => $this->content->overrideRichText($content[$field]['value'] ?? null),
                'default' => $field === 'title'
                    ? $this->content->emptyTranslations()
                    : ($rendered->getTranslations($field) ?: $this->content->emptyTranslations()),
                default => $original[$field] ?: [],
            };
            $updates[$field] = json_encode($translations);
        }
    }

    /**
     * @param  array<string, mixed>  $updates
     * @param  array<string, mixed>  $payload
     */
    private function mergeDocumentMeta(array &$updates, array $payload, string $dateColumn = 'issue_date', array $extraDates = []): void
    {
        if (array_key_exists($dateColumn, $payload) && filled($payload[$dateColumn])) {
            $updates[$dateColumn] = $payload[$dateColumn];
        }

        foreach ($extraDates as $column) {
            if (array_key_exists($column, $payload) && filled($payload[$column])) {
                $updates[$column] = $payload[$column];
            }
        }

        if (array_key_exists('status', $payload)) {
            $updates['status'] = (string) $payload['status'];
        }
    }

    /**
     * @param  array<string, mixed>  $updates
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $map
     */
    private function mergeSnapshot(array &$updates, array $payload, array $map): void
    {
        if (array_key_exists('client_id', $payload)) {
            $updates['client_id'] = $payload['client_id'];
        }

        $client = is_array($payload['client'] ?? null) ? $payload['client'] : [];

        foreach ($map as $modelField => $clientKey) {
            if (array_key_exists($modelField, $payload)) {
                $updates[$modelField] = $payload[$modelField];

                continue;
            }

            if (array_key_exists($clientKey, $client)) {
                $updates[$modelField] = $client[$clientKey];
            }
        }
    }

    /**
     * @param  array<string, mixed>  $updates
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $fields
     */
    private function mergeScalars(array &$updates, array $payload, array $fields): void
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $payload)) {
                $updates[$field] = $payload[$field];
            }
        }
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function dryRun(string $type, int $id, array $changes): array
    {
        return [
            'dry_run' => true,
            'valid' => true,
            'would_update' => [
                'type' => $type,
                'id' => $id,
                'fields' => array_keys(array_filter($changes, fn (mixed $value): bool => $value !== null)),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function previewKeys(array $payload): array
    {
        return array_diff_key($payload, array_flip(['dry_run']));
    }

    /**
     * @return array<string, mixed>
     */
    private function companyPayload(Company $company): array
    {
        $pics = [];

        foreach (is_array($company->pic) ? $company->pic : [] as $index => $pic) {
            if (! is_array($pic)) {
                continue;
            }

            $pics[] = [
                'index' => (int) $index,
                'pic_name' => (string) ($pic['pic_name'] ?? ''),
                'pic_role' => (string) ($pic['pic_role'] ?? ''),
            ];
        }

        return [
            'id' => $company->id,
            'company_name' => $company->company_name,
            'brand_name' => $company->brand_name,
            'address' => $company->address,
            'email_1' => $company->email_1,
            'phone_1' => $company->phone_1,
            'default_currency' => $company->default_currency,
            'pic' => $pics,
        ];
    }
}
