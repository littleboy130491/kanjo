<?php

namespace App\Filament\Admin\Resources\Concerns;

trait UsesPolling
{
    protected function poll(string $event, int $interval): void
    {
        $this->js(<<<JS
            setInterval(() => {
                {$event}
            }, {$interval} * 1000);
        JS);
    }
}
