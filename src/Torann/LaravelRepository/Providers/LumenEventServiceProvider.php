<?php

namespace Torann\LaravelRepository\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class LumenEventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        \Torann\LaravelRepository\Events\RepositoryEvent::class => [
            \Torann\LaravelRepository\Listeners\CleanCacheRepository::class
        ],
    ];
}
