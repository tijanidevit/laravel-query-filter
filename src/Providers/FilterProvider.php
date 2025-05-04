<?php

namespace Tijanidevit\QueryFilter\Providers;

use Illuminate\Support\ServiceProvider;
use Tijanidevit\QueryFilter\Support\FilterableMacros;

class FilterProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void {}

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        FilterableMacros::boot();
    }
}
