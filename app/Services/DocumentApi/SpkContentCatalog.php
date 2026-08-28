<?php

namespace App\Services\DocumentApi;

class SpkContentCatalog
{
    public const FIELD_KEYS = [
        'title',
        'party_identification',
        'subject',
        'content',
        'signature',
    ];

    public const PLACEHOLDERS = [
        'spk_number',
        'spk_date',
        'client_company',
        'client_pic_name',
        'client_pic_role',
        'client_address',
        'company_name',
        'company_pic_name',
        'company_pic_role',
        'company_address',
        'proposal_number',
        'proposal_date',
        'offer_name',
        'offer_price',
        'offer_name_1',
        'offer_price_1',
        'offer_name_2',
        'offer_price_2',
        'offer_timeline',
        'offer_timeline_1',
        'offer_timeline_2',
        'subject',
    ];
}
