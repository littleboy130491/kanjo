<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Company 1: Primary Agency
        Company::create([
            'company_name' => 'PT Web Design Indonesia',
            'brand_name' => 'WebDesign ID',
            'address' => "Jl. Sudirman No. 123\nJakarta Pusat 10220",
            'email_1' => 'info@webdesign.id',
            'email_2' => 'support@webdesign.id',
            'phone_1' => '+62 21 1234 5678',
            'phone_2' => '+62 21 8765 4321',
            'tax_id' => '09.123.456.7-123.000',
            'website' => 'https://webdesign.id',
            'default_currency' => 'IDR',
            'color_primary' => '#1e40af',
            'color_secondary' => '#3b82f6',
            'footer_text' => [
                'en' => 'Thank you for your business. We look forward to working with you.',
                'id' => 'Terima kasih atas kepercayaan Anda. Kami menantikan kerja sama yang baik.',
            ],
            'bank' => [
                [
                    'bank_name' => 'Bank Central Asia (BCA)',
                    'account_name' => 'PT Web Design Indonesia',
                    'account_number' => '1234567890',
                ],
                [
                    'bank_name' => 'Bank Mandiri',
                    'account_name' => 'PT Web Design Indonesia',
                    'account_number' => '0987654321',
                ],
            ],
            'pic' => [
                [
                    'pic_name' => 'John Doe',
                    'pic_role' => 'Project Manager',
                    'pic_sign' => null,
                ],
                [
                    'pic_name' => 'Jane Smith',
                    'pic_role' => 'Technical Lead',
                    'pic_sign' => null,
                ],
            ],
        ]);

        // Company 2: Secondary Brand
        Company::create([
            'company_name' => 'PT Digital Solutions',
            'brand_name' => 'DigiSol',
            'address' => "Jl. Thamrin No. 456\nJakarta Selatan 12190",
            'email_1' => 'hello@digisol.id',
            'email_2' => null,
            'phone_1' => '+62 21 5555 8888',
            'phone_2' => null,
            'tax_id' => '09.987.654.3-210.000',
            'website' => 'https://digisol.id',
            'default_currency' => 'IDR',
            'color_primary' => '#059669',
            'color_secondary' => '#10b981',
            'footer_text' => [
                'en' => 'Digital solutions for modern business.',
                'id' => 'Solusi digital untuk bisnis modern.',
            ],
            'bank' => [
                [
                    'bank_name' => 'Bank Rakyat Indonesia (BRI)',
                    'account_name' => 'PT Digital Solutions',
                    'account_number' => '1122334455',
                ],
            ],
            'pic' => [
                [
                    'pic_name' => 'Ahmad Fauzi',
                    'pic_role' => 'Director',
                    'pic_sign' => null,
                ],
            ],
        ]);

        // Company 3: International Brand
        Company::create([
            'company_name' => 'Global Web Agency Pte Ltd',
            'brand_name' => 'GlobalWeb',
            'address' => "123 Orchard Road\nSingapore 238895",
            'email_1' => 'contact@globalweb.sg',
            'email_2' => 'billing@globalweb.sg',
            'phone_1' => '+65 6123 4567',
            'phone_2' => null,
            'tax_id' => 'SG12345678A',
            'website' => 'https://globalweb.sg',
            'default_currency' => 'USD',
            'color_primary' => '#7c3aed',
            'color_secondary' => '#8b5cf6',
            'footer_text' => [
                'en' => 'Global web solutions for international clients.',
                'id' => 'Solusi web global untuk klien internasional.',
            ],
            'bank' => [
                [
                    'bank_name' => 'DBS Bank',
                    'account_name' => 'Global Web Agency Pte Ltd',
                    'account_number' => '001-234567-8',
                ],
            ],
            'pic' => [
                [
                    'pic_name' => 'Sarah Johnson',
                    'pic_role' => 'Regional Manager',
                    'pic_sign' => null,
                ],
            ],
        ]);
    }
}
