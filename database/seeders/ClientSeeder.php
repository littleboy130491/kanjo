<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Client 1: Indonesian Tech Startup
        Client::create([
            'name' => 'Budi Santoso',
            'company' => 'PT Teknologi Maju',
            'address' => 'Jl. Sudirman No. 10<br>Jakarta Pusat 10220',
            'email' => 'budi@teknologimaju.co.id',
            'phone' => '+62 812 3456 7890',
            'notes' => [
                [
                    'note' => 'Prefer communication via email',
                    'date' => '2026-01-15',
                ],
                [
                    'note' => 'Budget approved for Q1 2026',
                    'date' => '2026-01-20',
                ],
            ],
        ]);

        // Client 2: International E-commerce Company
        Client::create([
            'name' => 'Sarah Chen',
            'company' => 'ShopGlobal Pte Ltd',
            'address' => "1 Raffles Place\nSingapore 048616",
            'email' => 'sarah.chen@shopglobal.sg',
            'phone' => '+65 9123 4567',
            'notes' => [
                [
                    'note' => 'Requires English documentation',
                    'date' => '2026-02-01',
                ],
            ],
        ]);
    }
}
