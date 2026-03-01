<?php

namespace Database\Seeders;

use App\Models\ProposalContentDefault;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProposalContentDefaultSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        ProposalContentDefault::updateOrCreate([
            'field_key' => ProposalContentDefault::GLOBAL_FIELD_KEY,
        ], [
            'value' => [
                'en' => self::defaultValueEn(),
                'id' => self::defaultValueId(),
            ],
        ]);
    }

    /**
     * @return array<string, array<int, array<string, string>>>
     */
    private static function defaultValueEn(): array
    {
        return [
            'brief' => [
                ['content' => 'This proposal outlines website development and launch deliverables.'],
            ],
            'core_services' => [
                ['service' => 'Web Design & Development'],
                ['service' => 'CMS Setup'],
            ],
            'features' => [
                [
                    'feature_name' => 'Responsive Design',
                    'feature_description' => 'Optimized layout for desktop, tablet, and mobile.',
                ],
                [
                    'feature_name' => 'Admin Dashboard',
                    'feature_description' => 'Manage pages and content securely.',
                ],
            ],
            'server' => [
                ['item' => 'Cloud hosting setup'],
            ],
            'assets' => [
                ['item' => 'Logo files and brand assets'],
            ],
            'security' => [
                ['item' => 'Basic firewall and SSL'],
            ],
            'support' => [
                ['item' => '30 days post-launch support'],
            ],
            'additional_benefit' => [
                ['benefit' => 'Basic SEO setup included'],
            ],
            'add_on' => [
                [
                    'name' => 'Monthly Maintenance',
                    'description' => 'Content updates, monitoring, and minor fixes.',
                    'price' => '1500000',
                ],
            ],
            'payment' => [
                [
                    'info' => '50% down payment to start, 50% before launch.',
                    'down_payment_amount' => '5000000',
                ],
            ],
            'terms_condition' => [
                [
                    'title' => 'Revision Scope',
                    'description' => 'Up to 2 minor revisions per approved page.',
                ],
            ],
            'offer_1_project_timeline' => [
                [
                    'activity_name' => 'UI Design',
                    'activity_pic' => 'Design Team',
                    'activity_days' => '5',
                ],
                [
                    'activity_name' => 'Development',
                    'activity_pic' => 'Engineering Team',
                    'activity_days' => '10',
                ],
            ],
            'offer_2_project_timeline' => [
                [
                    'activity_name' => 'Template Setup',
                    'activity_pic' => 'Engineering Team',
                    'activity_days' => '4',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<int, array<string, string>>>
     */
    private static function defaultValueId(): array
    {
        return [
            'brief' => [
                ['content' => 'Proposal ini menjelaskan ruang lingkup pengembangan website hingga peluncuran.'],
            ],
            'core_services' => [
                ['service' => 'Desain & Pengembangan Website'],
                ['service' => 'Setup CMS'],
            ],
            'features' => [
                [
                    'feature_name' => 'Desain Responsif',
                    'feature_description' => 'Tampilan optimal untuk desktop, tablet, dan mobile.',
                ],
                [
                    'feature_name' => 'Dashboard Admin',
                    'feature_description' => 'Kelola halaman dan konten dengan aman.',
                ],
            ],
            'server' => [
                ['item' => 'Setup cloud hosting'],
            ],
            'assets' => [
                ['item' => 'File logo dan aset brand'],
            ],
            'security' => [
                ['item' => 'Firewall dasar dan SSL'],
            ],
            'support' => [
                ['item' => 'Dukungan 30 hari setelah go-live'],
            ],
            'additional_benefit' => [
                ['benefit' => 'Setup SEO dasar termasuk'],
            ],
            'add_on' => [
                [
                    'name' => 'Maintenance Bulanan',
                    'description' => 'Update konten, monitoring, dan perbaikan minor.',
                    'price' => '1500000',
                ],
            ],
            'payment' => [
                [
                    'info' => 'DP 50% saat mulai, 50% sebelum go-live.',
                    'down_payment_amount' => '5000000',
                ],
            ],
            'terms_condition' => [
                [
                    'title' => 'Ruang Lingkup Revisi',
                    'description' => 'Maksimal 2 revisi minor per halaman yang sudah disetujui.',
                ],
            ],
            'offer_1_project_timeline' => [
                [
                    'activity_name' => 'Desain UI',
                    'activity_pic' => 'Tim Desain',
                    'activity_days' => '5',
                ],
                [
                    'activity_name' => 'Development',
                    'activity_pic' => 'Tim Engineering',
                    'activity_days' => '10',
                ],
            ],
            'offer_2_project_timeline' => [
                [
                    'activity_name' => 'Setup Template',
                    'activity_pic' => 'Tim Engineering',
                    'activity_days' => '4',
                ],
            ],
        ];
    }
}
