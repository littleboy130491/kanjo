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

        // Company 3: International Brand
        Company::create([
            'company_name' => 'PT Digital Citra Kreatif',
            'brand_name' => 'Imajiner',
            'address' => "Jl. Tarumanegara II No.18a\nBogor Selatan, Jawa Barat 16137",
            'email_1' => 'admin@imajiner.id',
            'phone_1' => '+62 822-1049-1657',
            'phone_2' => '+62 852-1979-8588',
            'tax_id' => 'SG12345678A',
            'website' => 'https://imajiner.id',
            'google_maps_embed_url' => 'https://www.google.com/maps?cid=11640011655421333547&g_mp=CiVnb29nbGUubWFwcy5wbGFjZXMudjEuUGxhY2VzLkdldFBsYWNlEAMYASAF&hl=en-GB&source=embed',
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
        ]);
    }
}
