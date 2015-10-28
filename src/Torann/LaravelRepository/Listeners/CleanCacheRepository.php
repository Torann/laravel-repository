<?php

namespace Torann\LaravelRepository\Listeners;

use Torann\LaravelRepository\Events\RepositoryEntityEvent;

class CleanCacheRepository
{
    /**
     * Clear cache for the given repository.
     *
     * @param RepositoryEntityEvent $event
     */
    public function handle(RepositoryEntityEvent $event)
    {
        if (config('repositories.cache.clean.enabled', true))
        {
            $repository = $event->getRepository();
            $action = $event->getAction();

            if (in_array(config('cache.default'), ['file', 'database']) === false
                && config("repositories.cache.clean.on.{$action}", true)
            ) {
                app('Illuminate\Cache\CacheManager')->tags($repository)->flush();
            }
        }
    }
}