<?php

namespace Torann\LaravelRepository\Contracts;

use Closure;

interface CacheableInterface
{
    /**
     * Get an item from the cache, or store the default value.
     *
     * @param  string $method
     * @param  array|null $args
     * @param  \Closure $callback
     * @return mixed
     */
    public function getCache($method, $args = null, Closure $callback);

    /**
     * Get Cache key for the method
     *
     * @param $method
     * @param $args
     * @return string
     */
    public function getCacheKey($method, $args = null);

    /**
     * Get cache minutes
     *
     * @return int
     */
    public function getCacheMinutes();

    /**
     * Skip Cache
     *
     * @param bool $status
     * @return $this
     */
    public function skipCache($status = true);
}