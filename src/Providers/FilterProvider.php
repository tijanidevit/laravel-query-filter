<?php

namespace Tijanidevit\QueryFilter\Providers;

use Illuminate\Support\ServiceProvider;
use Tijanidevit\QueryFilter\Support\FilterableMacros;

class FilterProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void {
        $this->mergeConfigFrom(
            __DIR__.'/../config/query-filter.php', 'query-filter'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/query-filter.php' => config_path('query-filter.php'),
        ], 'config');
        FilterableMacros::boot();
    }
}
