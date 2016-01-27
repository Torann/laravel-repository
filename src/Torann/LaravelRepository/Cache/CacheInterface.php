<?php

namespace Torann\LaravelRepository\Cache;

use Closure;
use Torann\LaravelRepository\Repositories\RepositoryInterface;

interface CacheInterface
{
    /**
     * Get cache minutes.
     *
     * @return int
     */
    public function getMinutes();

    /**
     * Set cache minutes.
     *
     * @param int $minutes
     */
    public function setMinutes($minutes);

    /**
     * Fire repository event.
     *
     * @param  string              $event
     * @param  RepositoryInterface $repo
     */
    public function fire($event, RepositoryInterface $repo);

    /**
     * Get an item from the cache, or store the value.
     *
     * @param  string  $key
     * @param  Closure $callback
     * @param  int     $minutes
     *
     * @return mixed
     */
    public function remember($key, Closure $callback, $minutes = null);

    /**
     * Get tags.
     *
     * @return array
     */
    public function getTags();

    /**
     * Set tags.
     *
     * @param  array $tags
     *
     * @return void
     */
    public function addTags($tags);

    /**
     * Flush cache for tags.
     *
     * @param mixed $tags
     *
     * @return bool
     */
    public function flush($tags = null);
}