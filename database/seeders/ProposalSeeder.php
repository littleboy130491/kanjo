<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProposalSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $companies = Company::all();
        $users = User::all();

        if ($companies->isEmpty() || $users->isEmpty()) {
            $this->command->warn('Companies or Users not found. Please seed them first.');

            return;
        }

        // Proposal 1: Standard Website Package (Draft)
        Proposal::create([
            'client_company' => 'PT Maju Bersama',
            'client_name' => 'Budi Santoso',
            'client_email' => 'budi@majubersama.co.id',
            'issue_date' => now(),
            'valid_until' => now()->addDays(30),
            'currency' => 'IDR',
            'tax_rate' => 11,
            'tax_amount' => 5500000,
            'total_amount' => 55500000,
            'brief' => [
                'en' => [
                    ['content' => 'We need a modern corporate website to showcase our company profile and services.'],
                ],
                'id' => [
                    ['content' => 'Kami membutuhkan website korporat modern untuk menampilkan profil perusahaan dan layanan kami.'],
                ],
            ],
            'core_services' => [
                'en' => [
                    ['service' => 'Responsive Website Design'],
                    ['service' => 'Content Management System'],
                    ['service' => 'SEO Optimization'],
                ],
                'id' => [
                    ['service' => 'Desain Website Responsif'],
                    ['service' => 'Sistem Manajemen Konten'],
                    ['service' => 'Optimasi SEO'],
                ],
            ],
            'features' => [
                'en' => [
                    ['feature_name' => 'Mobile Responsive', 'feature_description' => 'Works perfectly on all devices'],
                    ['feature_name' => 'Fast Loading', 'feature_description' => 'Optimized for speed and performance'],
                ],
                'id' => [
                    ['feature_name' => 'Responsif Mobile', 'feature_description' => 'Berfungsi sempurna di semua perangkat'],
                    ['feature_name' => 'Pemuatan Cepat', 'feature_description' => 'Dioptimalkan untuk kecepatan dan performa'],
                ],
            ],
            'server' => [
                'en' => [
                    ['item' => 'Cloud Hosting with 99.9% Uptime'],
                    ['item' => 'SSL Certificate Included'],
                ],
                'id' => [
                    ['item' => 'Cloud Hosting dengan Uptime 99.9%'],
                    ['item' => 'Sertifikat SSL Termasuk'],
                ],
            ],
            'assets' => [
                'en' => [
                    ['asset' => 'Custom Graphics and Icons'],
                    ['asset' => 'Stock Photos (up to 50 images)'],
                ],
                'id' => [
                    ['asset' => 'Grafis dan Ikon Kustom'],
                    ['asset' => 'Foto Stok (hingga 50 gambar)'],
                ],
            ],
            'security' => [
                'en' => [
                    ['security_item' => 'Daily Backup'],
                    ['security_item' => 'DDoS Protection'],
                ],
                'id' => [
                    ['security_item' => 'Backup Harian'],
                    ['security_item' => 'Proteksi DDoS'],
                ],
            ],
            'support' => [
                'en' => [
                    ['support_item' => '3 Months Free Support'],
                    ['support_item' => 'Email Support Response within 24h'],
                ],
                'id' => [
                    ['support_item' => 'Dukungan Gratis 3 Bulan'],
                    ['support_item' => 'Respons Dukungan Email dalam 24j'],
                ],
            ],
            'additional_benefit' => [
                'en' => [
                    ['benefit' => 'Free Domain for 1 Year'],
                    ['benefit' => 'Google Analytics Setup'],
                ],
                'id' => [
                    ['benefit' => 'Domain Gratis 1 Tahun'],
                    ['benefit' => 'Setup Google Analytics'],
                ],
            ],
            'add_on' => [
                'en' => [
                    ['name' => 'Additional Pages', 'description' => 'Extra pages beyond package limit', 'price' => 1500000],
                    ['name' => 'Multilingual', 'description' => 'Add language switcher', 'price' => 3000000],
                ],
                'id' => [
                    ['name' => 'Halaman Tambahan', 'description' => 'Halaman ekstra di luar limit paket', 'price' => 1500000],
                    ['name' => 'Multibahasa', 'description' => 'Tambah pengalih bahasa', 'price' => 3000000],
                ],
            ],
            'payment' => [
                'en' => [
                    ['info' => '50% down payment to start project', 'down_payment_amount' => 25000000],
                    ['info' => '50% upon project completion', 'down_payment_amount' => null],
                ],
                'id' => [
                    ['info' => 'DP 50% untuk memulai proyek', 'down_payment_amount' => 25000000],
                    ['info' => '50% setelah proyek selesai', 'down_payment_amount' => null],
                ],
            ],
            'terms_condition' => [
                'en' => [
                    ['title' => 'Revision Policy', 'description' => 'Up to 3 rounds of revisions included'],
                    ['title' => 'Project Timeline', 'description' => 'Project will be completed within 30 working days'],
                ],
                'id' => [
                    ['title' => 'Kebijakan Revisi', 'description' => 'Hingga 3 putaran revisi termasuk'],
                    ['title' => 'Timeline Proyek', 'description' => 'Proyek akan selesai dalam 30 hari kerja'],
                ],
            ],
            'portfolios' => [
                ['portfolio_name' => 'Company Profile A', 'portfolio_image_url' => 'https://example.com/portfolio1.jpg', 'portfolio_link' => 'https://example1.com'],
                ['portfolio_name' => 'E-commerce B', 'portfolio_image_url' => 'https://example.com/portfolio2.jpg', 'portfolio_link' => 'https://example2.com'],
            ],
            'offer_name_1' => 'Standard Website Package',
            'offer_1_price' => 50000000,
            'offer_1_original_price' => 55000000,
            'offer_1_renewal_price' => 5000000,
            'offer_1_original_renewal_price' => 6000000,
            'offer_1_project_timeline' => [
                'en' => [
                    ['activity_name' => 'Design Phase', 'activity_pic' => 'Designer', 'activity_days' => 7],
                    ['activity_name' => 'Development', 'activity_pic' => 'Developer', 'activity_days' => 14],
                    ['activity_name' => 'Testing & Launch', 'activity_pic' => 'QA Team', 'activity_days' => 5],
                ],
                'id' => [
                    ['activity_name' => 'Fase Desain', 'activity_pic' => 'Desainer', 'activity_days' => 7],
                    ['activity_name' => 'Pengembangan', 'activity_pic' => 'Developer', 'activity_days' => 14],
                    ['activity_name' => 'Testing & Launch', 'activity_pic' => 'Tim QA', 'activity_days' => 5],
                ],
            ],
            'offer_name_2' => 'Premium Website Package',
            'offer_2_price' => 75000000,
            'offer_2_original_price' => 85000000,
            'offer_2_renewal_price' => 8000000,
            'offer_2_original_renewal_price' => 10000000,
            'offer_2_project_timeline' => [
                'en' => [
                    ['activity_name' => 'Design Phase', 'activity_pic' => 'Designer', 'activity_days' => 10],
                    ['activity_name' => 'Development', 'activity_pic' => 'Developer', 'activity_days' => 20],
                    ['activity_name' => 'Testing & Launch', 'activity_pic' => 'QA Team', 'activity_days' => 7],
                ],
                'id' => [
                    ['activity_name' => 'Fase Desain', 'activity_pic' => 'Desainer', 'activity_days' => 10],
                    ['activity_name' => 'Pengembangan', 'activity_pic' => 'Developer', 'activity_days' => 20],
                    ['activity_name' => 'Testing & Launch', 'activity_pic' => 'Tim QA', 'activity_days' => 7],
                ],
            ],
            'status' => 'draft',
            'access_username' => 'maju2024',
            'access_password' => bcrypt('proposal123'),
            'notes' => 'Client prefers meeting on weekends. Budget is flexible.',
            'user_id' => $users->first()->id,
            'company_id' => $companies->first()->id,
        ]);

        // Proposal 2: E-commerce Package (Published)
        Proposal::create([
            'client_company' => 'Toko Online Sukses',
            'client_name' => 'Siti Rahayu',
            'client_email' => 'siti@tokosukses.com',
            'issue_date' => now()->subDays(5),
            'valid_until' => now()->addDays(25),
            'currency' => 'IDR',
            'tax_rate' => 11,
            'tax_amount' => 11000000,
            'total_amount' => 111000000,
            'brief' => [
                'en' => [
                    ['content' => 'Full-featured e-commerce website with payment gateway integration.'],
                ],
                'id' => [
                    ['content' => 'Website e-commerce lengkap dengan integrasi payment gateway.'],
                ],
            ],
            'core_services' => [
                'en' => [
                    ['service' => 'E-commerce Platform'],
                    ['service' => 'Payment Gateway Integration'],
                    ['service' => 'Inventory Management'],
                ],
                'id' => [
                    ['service' => 'Platform E-commerce'],
                    ['service' => 'Integrasi Payment Gateway'],
                    ['service' => 'Manajemen Inventori'],
                ],
            ],
            'features' => [
                'en' => [
                    ['feature_name' => 'Product Management', 'feature_description' => 'Easy product upload and management'],
                    ['feature_name' => 'Order Tracking', 'feature_description' => 'Real-time order status tracking'],
                ],
                'id' => [
                    ['feature_name' => 'Manajemen Produk', 'feature_description' => 'Upload dan kelola produk dengan mudah'],
                    ['feature_name' => 'Pelacakan Pesanan', 'feature_description' => 'Lacak status pesanan real-time'],
                ],
            ],
            'portfolios' => [
                ['portfolio_name' => 'Online Store X', 'portfolio_image_url' => 'https://example.com/store1.jpg', 'portfolio_link' => 'https://store1.com'],
            ],
            'offer_name_1' => 'E-commerce Starter',
            'offer_1_price' => 100000000,
            'offer_1_original_price' => null,
            'offer_1_renewal_price' => 10000000,
            'offer_1_original_renewal_price' => null,
            'status' => 'published',
            'access_username' => 'tokosukses',
            'access_password' => bcrypt('ecommerce2024'),
            'notes' => 'Client is ready to start immediately.',
            'user_id' => $users->first()->id,
            'company_id' => $companies->first()->id,
        ]);

        // Proposal 3: USD Currency Example
        Proposal::create([
            'client_company' => 'Global Tech Solutions',
            'client_name' => 'Michael Chen',
            'client_email' => 'michael@globaltech.com',
            'issue_date' => now()->subDays(10),
            'valid_until' => now()->addDays(20),
            'currency' => 'USD',
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => 5000,
            'brief' => [
                'en' => [
                    ['content' => 'Corporate website redesign for international market.'],
                ],
                'id' => [
                    ['content' => 'Redesain website korporat untuk pasar internasional.'],
                ],
            ],
            'core_services' => [
                'en' => [
                    ['service' => 'UI/UX Design'],
                    ['service' => 'Frontend Development'],
                    ['service' => 'CMS Integration'],
                ],
                'id' => [
                    ['service' => 'Desain UI/UX'],
                    ['service' => 'Pengembangan Frontend'],
                    ['service' => 'Integrasi CMS'],
                ],
            ],
            'offer_name_1' => 'Corporate Redesign',
            'offer_1_price' => 5000,
            'offer_1_original_price' => 6000,
            'offer_1_renewal_price' => 500,
            'offer_1_original_renewal_price' => 600,
            'status' => 'published',
            'access_username' => 'globaltech',
            'access_password' => bcrypt('usdproposal456'),
            'notes' => 'International client, meetings via Zoom.',
            'user_id' => $users->last()->id,
            'company_id' => $companies->last()->id,
        ]);

        $this->command->info('Created 3 sample proposals successfully!');
    }
}
