<?php

namespace App\Filament\Admin\Resources\Concerns;

trait UsesPolling
{
    protected function poll(string $key, string $event, int $interval): void
    {
        $key = addslashes($key);

        $this->js(<<<JS
            window.__resourceLockPollers = window.__resourceLockPollers || {};

            if (window.__resourceLockPollers['{$key}']) {
                return;
            }

            window.__resourceLockPollers['{$key}'] = setInterval(() => {
                {$event}
            }, {$interval} * 1000);
        JS);
    }
}
