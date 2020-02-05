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
        $this->app->make('Luria\Modelmap\ModelmapController');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => base_path('config/modelmap.php'),
            ], 'config');
        }
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        include __DIR__.'/routes/web.php';

        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'modelmap');

        $this->app->bind('command.modelmap:draw', ModelMapCommand::class);

        $this->commands([
            'command.modelmap:draw',
        ]);
    }
}
