<?php

namespace Luria\Modelmap;

use Illuminate\Support\ServiceProvider;

class ModelmapServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make('Luria\Laravel-Modelmap\ModelmapController');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        include __DIR__.'/routes/web.php';
    }
}
