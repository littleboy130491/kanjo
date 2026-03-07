<?php

namespace App\Providers;

use App\Policies\ActivityPolicy;
use App\Policies\MediaPolicy;
use App\Policies\RolePolicy;
use Awcodes\Curator\Facades\Glide;
use Awcodes\Curator\Glide\SymfonyResponseFactory;
use Awcodes\Curator\Models\Media;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use SolutionForest\FilamentTranslateField\Facades\FilamentTranslateField;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Glide::serverConfig([
            'driver' => 'imagick',
            'response' => new SymfonyResponseFactory(app('request')),
            'source' => storage_path('app'),
            'source_path_prefix' => 'public',
            'cache' => storage_path('app'),
            'cache_path_prefix' => '.cache',
            'max_image_size' => 2000 * 2000,
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentTranslateField::defaultLocales(config('translatable.locales'));

        Gate::policy(Activity::class, ActivityPolicy::class);
        Gate::policy(Media::class, MediaPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
    }
}
