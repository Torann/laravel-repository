<?php

namespace Torann\LaravelRepository\Contracts;

interface CacheableInterface
{
    /**
     * Find data by multiple fields
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function getCache($method, $args = []);

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