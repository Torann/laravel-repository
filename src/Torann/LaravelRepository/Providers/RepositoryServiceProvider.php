<?php

namespace Torann\LaravelRepository\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register any other events for your application.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->isLumen() === false) {
            $this->publishes([
                __DIR__ . '/../../../config/repositories.php' => config_path('repositories.php')
            ]);

            $this->mergeConfigFrom(
                __DIR__ . '/../../../config/repositories.php', 'repositories'
            );
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->isLumen() === false) {
            $this->app->register(\Torann\LaravelRepository\Providers\EventServiceProvider::class);
        }
        else {
            $this->app->register(\Torann\LaravelRepository\Providers\LumenEventServiceProvider::class);
        }
    }

    /**
     * Check if package is running under Lumen app
     *
     * @return bool
     */
    protected function isLumen()
    {
        return str_contains($this->app->version(), 'Lumen') === true;
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