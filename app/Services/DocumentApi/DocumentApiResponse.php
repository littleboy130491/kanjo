<?php

namespace App\Services\DocumentApi;

use App\Models\Invoice;
use App\Models\Proposal;
use App\Models\Spk;
use Illuminate\Database\Eloquent\Model;

class DocumentApiResponse
{
    /**
     * @return array<string, mixed>
     */
    public static function document(Model $document): array
    {
        $type = match (true) {
            $document instanceof Proposal => 'proposal',
            $document instanceof Invoice => 'invoice',
            $document instanceof Spk => 'spk',
            default => throw new \InvalidArgumentException('Unsupported document type.'),
        };

        $slug = (string) $document->slug;

        return [
            'data' => [
                'type' => $type,
                'id' => $document->id,
                'document_number' => $document->document_number,
                'status' => $document->status?->value ?? $document->status,
                'public_url' => $slug !== '' ? route($type.'.show', ['slug' => $slug]) : null,
                'pdf_url' => $slug !== '' ? route('pdf.'.$type, ['slug' => $slug]) : null,
                'client_id' => $document->client_id,
            ],
        ];
    }
}
