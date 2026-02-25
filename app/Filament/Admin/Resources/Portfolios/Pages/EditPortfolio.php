<?php

namespace App\Filament\Admin\Resources\Portfolios\Pages;

use App\Filament\Admin\Resources\Portfolios\PortfolioResource;
use Filament\Resources\Pages\EditRecord;

class EditPortfolio extends EditRecord
{
    protected static string $resource = PortfolioResource::class;
}