<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('tracking.head_code', null);
        $this->migrator->add('tracking.body_code', null);
    }
};
