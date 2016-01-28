<?php

namespace Torann\LaravelRepository\Listeners;

use Torann\LaravelRepository\Events\RepositoryEventInterface;

class CleanCacheRepository
{
    /**
     * Clear cache for the given repository.
     *
     * @param RepositoryEventInterface $event
     */
    public function handle(RepositoryEventInterface $event)
    {
        if (config('repositories.cache.clean.on.' . $event->getAction(), true)) {
            $cache = $event->getCache();

            $cache->flush($cache->getTags());
        }
    }
}