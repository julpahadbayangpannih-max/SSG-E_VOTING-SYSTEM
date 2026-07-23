<?php

namespace App\Providers;

use App\Support\Branding;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Makes $brand (school_name, school_short_name, tagline, logo_url)
        // available in every Blade view without every controller having to
        // remember to pass it in. See App\Support\Branding for source/cache.
        View::share('brand', Branding::get());
    }
}
