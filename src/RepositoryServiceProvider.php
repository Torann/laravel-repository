<?php

namespace Torann\LaravelRepository;

use Illuminate\Support\ServiceProvider;
use Torann\LaravelRepository\Repositories\AbstractRepository;

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
            __DIR__ . '/../config/repositories.php', 'repositories'
        );

        // Get caching
        AbstractRepository::setCacheInstance($this->app['cache']);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->isLumen() === false) {
            $this->publishes([
                __DIR__ . '/../config/repositories.php' => config_path('repositories.php')
            ], 'config');
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
}