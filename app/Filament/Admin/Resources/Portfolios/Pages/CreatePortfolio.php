<?php

namespace App\Filament\Admin\Resources\Portfolios\Pages;

use App\Filament\Admin\Resources\Portfolios\PortfolioResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePortfolio extends CreateRecord
{
    protected static string $resource = PortfolioResource::class;
}