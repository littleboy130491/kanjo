<?php

namespace Database\Seeders;

use App\Enums\DocumentStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Portfolio;
use App\Models\Proposal;
use App\Models\ProposalContentDefault;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProposalSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
            ],
        );

        $company = Company::query()->firstOrCreate(
            ['brand_name' => 'Imajiner'],
            [
                'company_name' => 'PT Digital Citra Kreatif',
                'address' => "Jl. Tarumanegara II No.18a\nBogor Selatan, Jawa Barat 16137",
                'email_1' => 'admin@imajiner.id',
                'phone_1' => '+62 822-1049-1657',
                'tax_id' => 'SG12345678A',
                'website' => 'https://imajiner.id',
                'default_currency' => 'IDR',
                'bank' => [
                    [
                        'bank_name' => 'Bank Central Asia (BCA)',
                        'account_name' => 'PT DIGITAL CITRA KREATIF',
                        'account_number' => '002-8888-786',
                    ],
                ],
                'pic' => [
                    [
                        'pic_name' => 'Nesa',
                        'pic_role' => 'Finance Manager',
                    ],
                ],
            ],
        );

        $client = Client::query()->firstOrCreate(
            ['email' => 'budi@teknologimaju.co.id'],
            [
                'name' => 'Budi Santoso',
                'company' => 'PT Teknologi Maju',
                'phone' => '+62 812 3456 7890',
                'notes' => [
                    [
                        'note' => 'Seeded for proposal sample',
                        'date' => now()->toDateString(),
                    ],
                ],
            ],
        );

        $contentDefaults = ProposalContentDefault::query()
            ->where('field_key', ProposalContentDefault::GLOBAL_FIELD_KEY)
            ->first();
        $translations = $contentDefaults?->getTranslations('value') ?? [];

        $proposal = Proposal::query()->updateOrCreate(
            ['slug' => 'seed-proposal-full'],
            [
                'document_number_override' => false,
                'client_id' => $client->id,
                'client_company' => $client->company,
                'client_name' => $client->name,
                'client_email' => $client->email,
                'client_phone' => $client->phone,
                'issue_date' => now()->toDateString(),
                'valid_until' => now()->addDays(30)->toDateString(),
                'currency' => 'IDR',
                'tax_rate' => 11,
                'brief' => self::translatedFromDefaults(
                    $translations,
                    'brief',
                    '<p>Seeded brief content.</p>',
                ),
                'core_services' => self::translatedFromDefaults(
                    $translations,
                    'core_services',
                    '<p>Seeded core services content.</p>',
                ),
                'features' => self::translatedFromDefaults(
                    $translations,
                    'features',
                    '<p>Seeded features content.</p>',
                ),
                'server' => self::translatedFromDefaults(
                    $translations,
                    'server',
                    '<p>Seeded server content.</p>',
                ),
                'assets' => self::translatedFromDefaults(
                    $translations,
                    'assets',
                    '<p>Seeded assets content.</p>',
                ),
                'security' => self::translatedFromDefaults(
                    $translations,
                    'security',
                    '<p>Seeded security content.</p>',
                ),
                'support' => self::translatedFromDefaults(
                    $translations,
                    'support',
                    '<p>Seeded support content.</p>',
                ),
                'additional_benefit' => self::translatedFromDefaults(
                    $translations,
                    'additional_benefit',
                    '<p>Seeded additional benefit content.</p>',
                ),
                'payment' => self::translatedFromDefaults(
                    $translations,
                    'payment',
                    '<p>Seeded payment terms.</p>',
                ),
                'terms_condition' => self::translatedFromDefaults(
                    $translations,
                    'terms_condition',
                    '<p>Seeded terms and conditions.</p>',
                ),
                'additional_info' => self::translatedFromDefaults(
                    $translations,
                    'marketing_program',
                    '<p>Seeded additional info.</p>',
                ),
                'extra_content_brief' => self::translatedFromDefaults(
                    $translations,
                    'extra_content_brief',
                    '<p>Seeded extra brief content.</p>',
                ),
                'faq' => self::translatedFromDefaults(
                    $translations,
                    'faq',
                    '<details><summary>Seeded question?</summary><div data-type="detailsContent"><p>Seeded answer.</p></div></details>',
                ),
                'our_process' => self::translatedFromDefaults(
                    $translations,
                    'our_process',
                    '',
                ),
                'about_us' => self::translatedFromDefaults(
                    $translations,
                    'about_us',
                    '',
                ),
                'video_testimonials' => data_get($translations, 'en.video_testimonials', []),
                'client_logos' => data_get($translations, 'en.client_logos', []),
                'add_on' => self::translatedFromDefaults(
                    $translations,
                    'add_on',
                    [
                        [
                            'name' => 'Technical Support',
                            'description' => 'Seeded additional support option.',
                            'price' => 'Rp.600.000 / hour',
                        ],
                    ],
                ),
                'offer_name_1' => 'Business Website Package',
                'offer_1_price' => 6000000,
                'offer_1_original_price' => 7000000,
                'offer_1_renewal_price' => 2500000,
                'offer_1_original_renewal_price' => 3000000,
                'offer_1_project_timeline' => self::translatedFromDefaults(
                    $translations,
                    'business_project_timeline',
                    [
                        [
                            'activity_name' => 'Kickoff',
                            'activity_pic' => 'All',
                            'activity_days' => '2',
                        ],
                    ],
                ),
                'offer_name_2' => 'Prime Website Package',
                'offer_2_price' => 9000000,
                'offer_2_original_price' => 10000000,
                'offer_2_renewal_price' => 3500000,
                'offer_2_original_renewal_price' => 4000000,
                'offer_2_project_timeline' => self::translatedFromDefaults(
                    $translations,
                    'prime_project_timeline',
                    [
                        [
                            'activity_name' => 'Kickoff',
                            'activity_pic' => 'All',
                            'activity_days' => '2',
                        ],
                    ],
                ),
                'status' => DocumentStatus::PUBLISHED,
                'access_username' => 'proposal-client',
                'access_password' => 'proposal-password',
                'notes' => [
                    [
                        'note' => 'Seeded full proposal record.',
                        'date' => now()->toDateString(),
                    ],
                ],
                'user_id' => $user->id,
                'company_id' => $company->id,
            ],
        );

        $portfolioIds = Portfolio::query()->limit(3)->pluck('id')->all();

        if ($portfolioIds !== []) {
            $proposal->portfolios()->syncWithoutDetaching($portfolioIds);
        }
    }

    private static function translatedFromDefaults(
        array $translations,
        string $key,
        mixed $fallback,
    ): array {
        return [
            'en' => data_get($translations, "en.{$key}", $fallback),
            'id' => data_get($translations, "id.{$key}", $fallback),
        ];
    }
}
