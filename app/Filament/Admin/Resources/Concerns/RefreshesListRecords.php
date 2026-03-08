<?php

namespace App\Filament\Admin\Resources\Concerns;

trait RefreshesListRecords
{
    use UsesPolling;

    protected int $listRefreshInterval = 15;

    public function getListRefreshInterval(): int
    {
        return $this->listRefreshInterval;
    }

    public function bootedRefreshesListRecords(): void
    {
        $this->poll('$wire.$refresh()', $this->getListRefreshInterval());
    }
}
