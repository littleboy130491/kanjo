<?php

namespace App\Services\DocumentApi;

use App\Enums\DocumentStatus;
use App\Filament\Admin\Resources\Proposals\Actions\CreateSpkAction;
use App\Models\Company;
use App\Models\Proposal;
use App\Models\Spk;
use App\Services\DocumentNumberGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SpkCreator
{
    public function __construct(
        private readonly ClientSnapshotResolver $clients,
        private readonly ContentResolver $content,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function handleStandalone(array $payload): array
    {
        $dryRun = (bool) ($payload['dry_run'] ?? false);
        $author = DocumentApiAuthor::user();
        $company = $this->company($payload);
        $pic = $this->companyPic($company, $payload);
        $spkDate = $this->date($payload['spk_date'] ?? null) ?? now();

        if ($dryRun) {
            $snapshot = $this->clients->resolve($payload, persist: false);
            $preview = DocumentNumberGenerator::preview('SPK', $spkDate);
            $spk = $this->makeUnsavedSpk($snapshot, $company, $pic, $author->id, $spkDate, $preview['document_number']);
            $resolved = $this->content->resolveSpkContent(
                is_array($payload['content'] ?? null) ? $payload['content'] : [],
                $spk,
            );

            return [
                'dry_run' => true,
                'valid' => true,
                'would_create' => [
                    'type' => 'spk',
                    'document_number' => $preview['document_number'],
                    'client' => [
                        'action' => $snapshot['action'],
                        'company' => $snapshot['client_company'],
                        'name' => $snapshot['client_name'],
                        'client_id' => $snapshot['client_id'],
                    ],
                    'company_pic_name' => $pic['pic_name'],
                    'company_pic_role' => $pic['pic_role'],
                ],
                'resolved_content_preview' => $resolved,
                'warnings' => $this->picWarnings($company, $payload),
            ];
        }

        return DB::transaction(function () use ($payload, $author, $company, $pic, $spkDate): array {
            $snapshot = $this->clients->resolve($payload, persist: true);
            $spk = Spk::query()->create([
                'spk_date' => $spkDate->toDateString(),
                'client_company' => $snapshot['client_company'],
                'client_pic_name' => $snapshot['client_name'],
                'client_pic_role' => $snapshot['client_pic_role'] ?? '',
                'client_address' => $snapshot['client_address'],
                'company_name' => (string) $company->company_name,
                'company_pic_name' => $pic['pic_name'],
                'company_pic_role' => $pic['pic_role'],
                'company_address' => $company->address,
                'status' => DocumentStatus::PUBLISHED,
                'activate_translation' => (bool) ($payload['activate_translation'] ?? false),
                'client_id' => $snapshot['client_id'],
                'company_id' => $company->id,
                'user_id' => $author->id,
                'updated_by' => $author->id,
                'notes' => [],
            ]);
            $resolved = $this->content->resolveSpkContent(
                is_array($payload['content'] ?? null) ? $payload['content'] : [],
                $spk,
            );
            $this->assignContent($spk, $resolved);
            $spk->saveQuietly();
            $spk->refresh();

            return DocumentApiResponse::document($spk);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function handleFromProposal(Proposal $proposal, array $payload): array
    {
        $dryRun = (bool) ($payload['dry_run'] ?? false);
        $author = DocumentApiAuthor::user();
        $proposal->loadMissing('company');
        $company = $proposal->company;

        if (! $company instanceof Company) {
            throw ValidationException::withMessages([
                'company_id' => 'The proposal has no issuing company.',
            ]);
        }

        $pic = $this->companyPic($company, $payload);
        $offerIndex = (int) ($payload['offer'] ?? $payload['offer_index'] ?? 1);
        $spkDate = now();

        if ($dryRun) {
            $preview = DocumentNumberGenerator::preview('SPK', $spkDate);
            $spk = new Spk([
                'spk_date' => $spkDate->toDateString(),
                'document_number' => $preview['document_number'],
                'client_company' => (string) $proposal->client_company,
                'client_pic_name' => (string) $proposal->client_name,
                'client_pic_role' => (string) ($payload['client']['pic_role'] ?? ''),
                'client_address' => $proposal->client_address,
                'company_name' => (string) $company->company_name,
                'company_pic_name' => $pic['pic_name'],
                'company_pic_role' => $pic['pic_role'],
                'company_address' => $company->address,
            ]);
            $resolved = $this->content->resolveSpkContent(
                is_array($payload['content'] ?? null) ? $payload['content'] : [],
                $spk,
                $proposal,
                $offerIndex,
            );

            return [
                'dry_run' => true,
                'valid' => true,
                'would_create' => [
                    'type' => 'spk',
                    'document_number' => $preview['document_number'],
                    'client' => [
                        'action' => 'existing',
                        'company' => $proposal->client_company,
                        'name' => $proposal->client_name,
                        'client_id' => $proposal->client_id,
                    ],
                    'proposal_id' => $proposal->id,
                    'company_pic_name' => $pic['pic_name'],
                    'company_pic_role' => $pic['pic_role'],
                ],
                'resolved_content_preview' => $resolved,
                'warnings' => $this->picWarnings($company, $payload),
            ];
        }

        return DB::transaction(function () use ($proposal, $payload, $author, $pic, $offerIndex): array {
            $spk = CreateSpkAction::createSpkFromProposal($proposal, [
                'company_pic_name' => $pic['pic_name'],
                'company_pic_role' => $pic['pic_role'],
                'offer_index' => $offerIndex,
            ]);
            $spk->user_id = $author->id;
            $spk->updated_by = $author->id;

            if (filled(data_get($payload, 'client.pic_role'))) {
                $spk->client_pic_role = (string) data_get($payload, 'client.pic_role');
            }

            if (array_key_exists('activate_translation', $payload)) {
                $spk->activate_translation = (bool) $payload['activate_translation'];
            }

            $resolved = $this->content->resolveSpkContent(
                is_array($payload['content'] ?? null) ? $payload['content'] : [],
                $spk,
                $proposal,
                $offerIndex,
            );
            $this->assignContent($spk, $resolved);
            $spk->saveQuietly();
            $spk->refresh();

            return DocumentApiResponse::document($spk);
        });
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array{pic_name: string, pic_role: string}  $pic
     */
    private function makeUnsavedSpk(
        array $snapshot,
        Company $company,
        array $pic,
        int $userId,
        Carbon $spkDate,
        string $documentNumber,
    ): Spk {
        return new Spk([
            'spk_date' => $spkDate->toDateString(),
            'document_number' => $documentNumber,
            'client_company' => $snapshot['client_company'],
            'client_pic_name' => $snapshot['client_name'],
            'client_pic_role' => $snapshot['client_pic_role'] ?? '',
            'client_address' => $snapshot['client_address'],
            'company_name' => (string) $company->company_name,
            'company_pic_name' => $pic['pic_name'],
            'company_pic_role' => $pic['pic_role'],
            'company_address' => $company->address,
            'user_id' => $userId,
            'company_id' => $company->id,
            'client_id' => $snapshot['client_id'],
        ]);
    }

    /**
     * @param  array<string, array<string, string>>  $resolved
     */
    private function assignContent(Spk $spk, array $resolved): void
    {
        foreach (SpkContentCatalog::FIELD_KEYS as $field) {
            $spk->setTranslations($field, $resolved[$field] ?? $this->content->emptyTranslations());
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{pic_name: string, pic_role: string}
     */
    private function companyPic(Company $company, array $payload): array
    {
        if (filled($payload['company_pic_name'] ?? null)) {
            return [
                'pic_name' => (string) $payload['company_pic_name'],
                'pic_role' => (string) ($payload['company_pic_role'] ?? ''),
            ];
        }

        $pics = is_array($company->pic) ? $company->pic : [];
        $index = array_key_exists('company_pic_index', $payload)
            ? (int) $payload['company_pic_index']
            : 0;
        $pic = is_array($pics[$index] ?? null) ? $pics[$index] : (is_array($pics[0] ?? null) ? $pics[0] : []);

        return [
            'pic_name' => (string) ($pic['pic_name'] ?? ''),
            'pic_role' => (string) ($pic['pic_role'] ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, string>
     */
    private function picWarnings(Company $company, array $payload): array
    {
        if (filled($payload['company_pic_name'] ?? null)) {
            return [];
        }

        $pics = is_array($company->pic) ? $company->pic : [];

        if ($pics === []) {
            return ['Company has no PIC records. company_pic_name will be empty.'];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function company(array $payload): Company
    {
        $company = Company::query()->find($payload['company_id'] ?? null);

        if (! $company instanceof Company) {
            throw ValidationException::withMessages([
                'company_id' => 'The selected company id is invalid.',
            ]);
        }

        return $company;
    }

    private function date(mixed $value): ?Carbon
    {
        if (! filled($value)) {
            return null;
        }

        return Carbon::parse((string) $value);
    }
}
