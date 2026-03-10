<?php

use DateTimeInterface;
use DateTimeZone;

return [
    'settings' => [
        'App\\Settings\\TrackingSettings',
    ],

    'setting_class_path' => app_path('Settings'),

    'migrations_paths' => [
        database_path('settings'),
    ],

    'default_repository' => 'database',

    'repositories' => [
        'database' => [
            'type' => 'Spatie\\LaravelSettings\\SettingsRepositories\\DatabaseSettingsRepository',
            'model' => null,
            'table' => 'settings',
            'connection' => null,
        ],
        'redis' => [
            'type' => 'Spatie\\LaravelSettings\\SettingsRepositories\\RedisSettingsRepository',
            'connection' => null,
            'prefix' => null,
        ],
    ],

    'encoder' => null,
    'decoder' => null,

    'cache' => [
        'enabled' => env('SETTINGS_CACHE_ENABLED', false),
        'store' => null,
        'prefix' => null,
        'ttl' => null,
    ],

    'global_casts' => [
        DateTimeInterface::class => 'Spatie\\LaravelSettings\\SettingsCasts\\DateTimeInterfaceCast',
        DateTimeZone::class => 'Spatie\\LaravelSettings\\SettingsCasts\\DateTimeZoneCast',
    ],

    'auto_discover_settings' => [
        app_path('Settings'),
    ],

    'cache_path' => storage_path('app/laravel-settings'),
];
