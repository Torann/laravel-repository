<?php

namespace Torann\LaravelRepository\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register any other events for your application.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes(array(
            __DIR__ . '/../../../resources/config/repositories.php' => config_path('repositories.php')
        ));

        $this->mergeConfigFrom(
            __DIR__ . '/../../../resources/config/repositories.php', 'repositories'
        );
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        dd('register: Torann\LaravelRepository\Providers\EventServiceProvider');

        $this->commands('Torann\LaravelRepository\Console\Commands\MakeRepositoryCommand');
        $this->commands('Torann\LaravelRepository\Console\Commands\MakeCriteriaCommand');
        $this->app->register('Torann\LaravelRepository\Providers\EventServiceProvider');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
