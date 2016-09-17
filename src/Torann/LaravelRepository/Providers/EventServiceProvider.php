<?php

namespace Torann\LaravelRepository\Providers;

//use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        \Torann\LaravelRepository\Events\RepositoryEvent::class => [
            \Torann\LaravelRepository\Listeners\CleanCacheRepository::class
        ],
    ];

    /**
     * Register any other events for your application.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function boot(/*DispatcherContract $events*/)
    {
        //parent::boot($events);
        foreach ($this->listens() as $event => $listeners) {
            foreach ($listeners as $listener) {
                \Event::listen($event, $listener);
            }
        }

        foreach ($this->subscribe as $subscriber) {
            \Event::subscribe($subscriber);
        }
    }
}