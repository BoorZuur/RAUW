<?php

namespace App\Providers;

use App\Support\ActorDistrictAccess;
use Illuminate\Support\ServiceProvider;

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
        $this->app->terminating(static fn () => ActorDistrictAccess::flush());
    }
}
