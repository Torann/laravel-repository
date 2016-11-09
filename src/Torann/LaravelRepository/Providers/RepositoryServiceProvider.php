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
        $this->mergeConfigFrom(
            __DIR__ . '/../../../config/repositories.php', 'repositories'
        );
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // Determine which service type to register
        $service = $this->isLumen() ? 'registerLumen' : 'registerLaravel';

        $this->$service();
    }

    /**
     * Register the Laravel application services.
     *
     * @return void
     */
    public function registerLaravel()
    {
        $this->app->register(\Torann\LaravelRepository\Providers\EventServiceProvider::class);

        $this->publishes([
            __DIR__ . '/../../../config/repositories.php' => config_path('repositories.php')
        ], 'config');
    }

    /**
     * Register the Lumen application services.
     *
     * @return void
     */
    public function registerLumen()
    {
        $this->app->register(\Torann\LaravelRepository\Providers\LumenEventServiceProvider::class);
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
}