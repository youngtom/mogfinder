<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
	    $currentModVersion = \App\StoredVariable::where('var', '=', 'current_mod_version')->first()->val;
        view()->share('currentModVersion', $currentModVersion);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment() == 'local') {
	        $this->app->register('Laracasts\Generators\GeneratorsServiceProvider');
	    }
    }
}
