<?php

namespace LaravelRequire;

use Illuminate\Support\ServiceProvider;
use LaravelRequire\Console\Commands\RequireCommand;

class LaravelRequireServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands();
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
    
    /**
     * Register the artisan commands.
     *
     * @return void
     */
    private function registerCommands()
    {
        $this->commands([
          \LaravelRequire\Console\Commands\RequireCommand::class
        ]);
    }

}
