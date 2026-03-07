<?php

namespace App\Providers;

use App\Policies\ActivityPolicy;
use App\Policies\MediaPolicy;
use App\Policies\RolePolicy;
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
        //
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
