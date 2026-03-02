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
     * @return array<string, mixed>
     */
    private static function defaultValueEn(): array
    {
        return [
            'brief' => '<p>Thank you for requesting a quotation for your professional website needs. We hope to be the right partner for you.</p><p>Based on our discussion with the consulting team, here are your website requirements:</p><ol><li>Modern design that highlights the credibility and professionalism of your business or company</li><li>Responsive display that adapts to each device (desktop, mobile phone, and tablet), so the website layout always appears optimally</li><li>A fast and secure website for users to access</li></ol>',
            'core_services' => '<ul><li>Web Design &amp; Development</li><li>CMS Setup</li></ul>',
            'features' => '<ul><li><strong>Responsive Design</strong> — Optimized layout for desktop, tablet, and mobile.</li><li><strong>Admin Dashboard</strong> — Manage pages and content securely.</li></ul>',
            'server' => '<ul><li>Cloud hosting setup</li></ul>',
            'assets' => '<ul><li>Logo files and brand assets</li></ul>',
            'security' => '<ul><li>Basic firewall and SSL</li></ul>',
            'support' => '<ul><li>30 days post-launch support</li></ul>',
            'additional_benefit' => '<ul><li>Basic SEO setup included</li></ul>',
            'payment' => '<p>50% down payment to start, 50% before launch.</p>',
            'terms_condition' => '<p><strong>Revision Scope</strong></p><p>Up to 2 minor revisions per approved page.</p>',
            'additional_info' => '',
            'extra_content_brief' => '',
            'add_on' => [
                [
                    'name' => 'Monthly Maintenance',
                    'description' => 'Content updates, monitoring, and minor fixes.',
                    'price' => '1500000',
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
     * @return array<string, mixed>
     */
    private static function defaultValueId(): array
    {
        return [
            'brief' => '<p>Terima kasih atas permintaan penawaran untuk kebutuhan website profesional Anda. Kami berharap dapat menjadi partner yang tepat untuk Anda.</p><p>Sesuai hasil diskusi dengan tim konsultan kami, berikut adalah kebutuhan website Anda:</p><ol><li>Desain modern yang menonjolkan kredibilitas dan profesionalisme bisnis / perusahaan</li><li>Tampilan responsive menyesuaikan device (desktop, handphone, dan tablet) sehingga layout website selalu tampil secara optimal</li><li>Website cepat dan aman untuk diakses</li></ol>',
            'core_services' => '<ul><li>Desain &amp; Pengembangan Website</li><li>Setup CMS</li></ul>',
            'features' => '<ul><li><strong>Desain Responsif</strong> — Tampilan optimal untuk desktop, tablet, dan mobile.</li><li><strong>Dashboard Admin</strong> — Kelola halaman dan konten dengan aman.</li></ul>',
            'server' => '<ul><li>Setup cloud hosting</li></ul>',
            'assets' => '<ul><li>File logo dan aset brand</li></ul>',
            'security' => '<ul><li>Firewall dasar dan SSL</li></ul>',
            'support' => '<ul><li>Dukungan 30 hari setelah go-live</li></ul>',
            'additional_benefit' => '<ul><li>Setup SEO dasar termasuk</li></ul>',
            'payment' => '<p>DP 50% saat mulai, 50% sebelum go-live.</p>',
            'terms_condition' => '<p><strong>Ruang Lingkup Revisi</strong></p><p>Maksimal 2 revisi minor per halaman yang sudah disetujui.</p>',
            'additional_info' => '',
            'extra_content_brief' => '',
            'add_on' => [
                [
                    'name' => 'Maintenance Bulanan',
                    'description' => 'Update konten, monitoring, dan perbaikan minor.',
                    'price' => '1500000',
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
