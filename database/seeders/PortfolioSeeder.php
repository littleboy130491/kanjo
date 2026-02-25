<?php

namespace Database\Seeders;

use App\Models\Portfolio;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PortfolioSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Portfolio items - image_url will be set via Curator in admin panel
        $portfolios = [
            [
                'name' => 'E-Commerce Platform for Fashion Brand',
                'url_link' => 'https://fashion-store.example.com',
            ],
            [
                'name' => 'Corporate Website for Tech Startup',
                'url_link' => 'https://techstartup.example.com',
            ],
            [
                'name' => 'Restaurant Booking System',
                'url_link' => 'https://restaurant-booking.example.com',
            ],
            [
                'name' => 'Real Estate Property Portal',
                'url_link' => 'https://realestate.example.com',
            ],
            [
                'name' => 'Healthcare Patient Portal',
                'url_link' => 'https://healthcare.example.com',
            ],
            [
                'name' => 'Educational Platform for Online Courses',
                'url_link' => 'https://elearning.example.com',
            ],
            [
                'name' => 'Mobile App Landing Page',
                'url_link' => 'https://mobileapp.example.com',
            ],
            [
                'name' => 'Financial Services Dashboard',
                'url_link' => 'https://finance-dashboard.example.com',
            ],
        ];

        foreach ($portfolios as $portfolio) {
            Portfolio::create($portfolio);
        }
    }
}