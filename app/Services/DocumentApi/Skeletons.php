<?php

namespace App\Services\DocumentApi;

class Skeletons
{
    /**
     * @return array<string, mixed>
     */
    public static function proposal(): array
    {
        $content = [];

        foreach (ProposalContentCatalog::fieldKeys() as $field) {
            $content[$field] = ['mode' => 'default'];
        }

        return [
            'payload' => [
                'dry_run' => true,
                'company_id' => null,
                'client' => [
                    'company' => '',
                    'name' => '',
                    'email' => '',
                    'phone' => '',
                    'address' => '',
                ],
                'offer_name_1' => '',
                'offer_1_price' => null,
                'offer_1_renewal_price' => null,
                'content_default_id' => null,
                'timeline_template' => 'short_project_timeline',
                'content' => $content,
            ],
            'content_fields' => ProposalContentCatalog::fieldKeys(),
            'timeline_templates' => ProposalContentCatalog::TIMELINE_TEMPLATES,
            'notes' => [
                'Set company_id from GET /api/v1/companies.',
                'Every content key is required. Start from this skeleton and change modes.',
                'content_default_id selects a pack from GET /content-defaults/proposal. Omit it to use the pack marked Default.',
                'timeline_template applies when offer_1_project_timeline / offer_2_project_timeline mode is default.',
                'POST with dry_run true first, then false to publish.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function invoice(): array
    {
        return [
            'payload' => [
                'dry_run' => true,
                'company_id' => null,
                'client' => [
                    'company' => '',
                    'name' => '',
                    'email' => '',
                    'phone' => '',
                    'address' => '',
                ],
                'items' => [
                    [
                        'title' => '',
                        'price' => 0,
                        'description' => '',
                    ],
                ],
                'content' => [
                    'additional_info' => ['mode' => 'empty'],
                ],
            ],
            'content_fields' => ['additional_info'],
            'notes' => [
                'Invoices have no content defaults. additional_info mode must be override or empty.',
                'items is required. A flat list is copied to both en and id.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function spk(): array
    {
        $content = [];

        foreach (SpkContentCatalog::FIELD_KEYS as $field) {
            $content[$field] = ['mode' => 'default'];
        }

        return [
            'payload' => [
                'dry_run' => true,
                'company_id' => null,
                'company_pic_index' => 0,
                'client' => [
                    'company' => '',
                    'name' => '',
                    'email' => '',
                    'phone' => '',
                    'address' => '',
                    'pic_role' => '',
                ],
                'content' => $content,
            ],
            'content_fields' => SpkContentCatalog::FIELD_KEYS,
            'placeholders' => SpkContentCatalog::PLACEHOLDERS,
            'notes' => [
                'title, party_identification, subject, and content default copy SPK Content Defaults and resolve placeholders once.',
                'Empty title or party_identification falls back to the auto-generated cover/party block on the public document.',
                'company_pic_index is 0-based from GET /api/v1/companies/{id}.',
            ],
        ];
    }
}
