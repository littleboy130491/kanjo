<?php

namespace App\Services\DocumentApi;

class OpenApiSpec
{
    /**
     * @return array<string, mixed>
     */
    public static function document(): array
    {
        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Kanjo Document API',
                'version' => '1.0.0',
                'description' => 'Kanjo Document API. The canonical agent manual is GET /api/v1/guide (markdown). Read that before calling other endpoints. OpenAPI here is a path index, not a complete contract.',
            ],
            'servers' => [
                ['url' => url('/api/v1')],
            ],
            'security' => [
                ['bearerAuth' => []],
                ['apiKeyAuth' => []],
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                    ],
                    'apiKeyAuth' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-Api-Key',
                    ],
                ],
                'schemas' => [
                    'ContentMode' => [
                        'type' => 'object',
                        'required' => ['mode'],
                        'properties' => [
                            'mode' => [
                                'type' => 'string',
                                'enum' => ['default', 'override', 'empty'],
                            ],
                            'value' => [
                                'description' => 'Required when mode is override. Rich text: markdown or HTML, as a string or {en,id}. Repeaters: JSON array or {en,id}.',
                            ],
                            'template' => [
                                'type' => 'string',
                                'description' => 'Optional defaults lookup key when mode is default.',
                            ],
                        ],
                    ],
                    'ClientInput' => [
                        'type' => 'object',
                        'properties' => [
                            'company' => ['type' => 'string'],
                            'name' => ['type' => 'string'],
                            'email' => ['type' => 'string'],
                            'phone' => ['type' => 'string'],
                            'address' => ['type' => 'string'],
                            'pic_role' => ['type' => 'string'],
                        ],
                    ],
                    'DocumentResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => [
                                'type' => 'object',
                                'properties' => [
                                    'type' => ['type' => 'string'],
                                    'id' => ['type' => 'integer'],
                                    'document_number' => ['type' => 'string'],
                                    'status' => ['type' => 'string', 'enum' => ['published']],
                                    'public_url' => ['type' => 'string'],
                                    'pdf_url' => ['type' => 'string'],
                                    'client_id' => ['type' => 'integer'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'paths' => [
                '/' => ['get' => ['summary' => 'API index', 'responses' => ['200' => ['description' => 'OK']]]],
                '/guide' => ['get' => ['summary' => 'Agent guide (markdown)', 'responses' => ['200' => ['description' => 'OK']]]],
                '/openapi.json' => ['get' => ['summary' => 'This OpenAPI document', 'responses' => ['200' => ['description' => 'OK']]]],
                '/companies' => ['get' => ['summary' => 'List companies', 'responses' => ['200' => ['description' => 'OK']]]],
                '/companies/{id}' => ['get' => ['summary' => 'Show company', 'responses' => ['200' => ['description' => 'OK'], '404' => ['description' => 'Not found']]]],
                '/clients' => ['get' => [
                    'summary' => 'Search clients',
                    'parameters' => [[
                        'name' => 'q',
                        'in' => 'query',
                        'schema' => ['type' => 'string'],
                    ]],
                    'responses' => ['200' => ['description' => 'OK']],
                ]],
                '/clients/{id}' => ['get' => ['summary' => 'Client with related proposals, invoices, services, SPKs', 'responses' => ['200' => ['description' => 'OK'], '404' => ['description' => 'Not found']]]],
                '/services' => ['get' => ['summary' => 'Search services', 'responses' => ['200' => ['description' => 'OK']]]],
                '/services/{id}' => ['get' => ['summary' => 'Show service and related invoices', 'responses' => ['200' => ['description' => 'OK'], '404' => ['description' => 'Not found']]]],
                '/content-defaults/proposal' => ['get' => ['summary' => 'Proposal content defaults', 'responses' => ['200' => ['description' => 'OK']]]],
                '/content-defaults/spk' => ['get' => ['summary' => 'SPK content defaults', 'responses' => ['200' => ['description' => 'OK']]]],
                '/proposals/skeleton' => ['get' => ['summary' => 'Proposal payload skeleton', 'responses' => ['200' => ['description' => 'OK']]]],
                '/invoices/skeleton' => ['get' => ['summary' => 'Invoice payload skeleton', 'responses' => ['200' => ['description' => 'OK']]]],
                '/spks/skeleton' => ['get' => ['summary' => 'SPK payload skeleton', 'responses' => ['200' => ['description' => 'OK']]]],
                '/proposals' => [
                    'get' => ['summary' => 'Search proposals', 'responses' => ['200' => ['description' => 'OK']]],
                    'post' => ['summary' => 'Create proposal', 'responses' => ['200' => ['description' => 'Dry-run or created'], '422' => ['description' => 'Validation error']]],
                ],
                '/invoices' => [
                    'get' => ['summary' => 'Search invoices', 'responses' => ['200' => ['description' => 'OK']]],
                    'post' => ['summary' => 'Create standalone invoice', 'responses' => ['200' => ['description' => 'OK'], '422' => ['description' => 'Validation error']]],
                ],
                '/spks' => [
                    'get' => ['summary' => 'Search SPKs', 'responses' => ['200' => ['description' => 'OK']]],
                    'post' => ['summary' => 'Create standalone SPK', 'responses' => ['200' => ['description' => 'OK'], '422' => ['description' => 'Validation error']]],
                ],
                '/proposals/{id}' => ['get' => ['summary' => 'Show proposal summary', 'responses' => ['200' => ['description' => 'OK'], '404' => ['description' => 'Not found']]]],
                '/invoices/{id}' => ['get' => ['summary' => 'Show invoice summary', 'responses' => ['200' => ['description' => 'OK'], '404' => ['description' => 'Not found']]]],
                '/spks/{id}' => ['get' => ['summary' => 'Show SPK summary', 'responses' => ['200' => ['description' => 'OK'], '404' => ['description' => 'Not found']]]],
                '/proposals/{id}/invoices' => ['post' => ['summary' => 'Create invoice from proposal', 'responses' => ['200' => ['description' => 'OK'], '404' => ['description' => 'Not found']]]],
                '/proposals/{id}/spks' => ['post' => ['summary' => 'Create SPK from proposal', 'responses' => ['200' => ['description' => 'OK'], '404' => ['description' => 'Not found']]]],
            ],
        ];
    }
}
