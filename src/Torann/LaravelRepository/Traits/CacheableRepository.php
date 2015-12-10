<?php

namespace Torann\LaravelRepository\Traits;

trait CacheableRepository
{
    /**
     * Skip Cache
     *
     * @param bool $status
     * @return self
     */
    public function skipCache($status = true)
    {
        $this->cacheSkip = $status;

        return $this;
    }

    /**
     * Determine if the cache will be skipped
     *
     * @return bool
     */
    public function isSkippedCache()
    {
        // Check to ensure caching is supported
        if (in_array(config('cache.default'), ['file', 'database'])) {
            $this->skipCache(true);
        }

        // Check repository for caching
        $skipped = isset($this->cacheSkip) ? $this->cacheSkip : false;

        // Check request for cache override
        if (request(config('repositories.cache.params.skipCache', 'skipCache'))) {
            $skipped = true;
        }

        return $skipped;
    }

    /**
     * @param $method
     * @return bool
     */
    protected function allowedCache($method)
    {
        $cacheEnabled = config('repositories.cache.enabled', false);

        if (! $cacheEnabled) {
            return false;
        }

        $cacheOnly = isset($this->cacheOnly) ? $this->cacheOnly : config('repositories.cache.allowed.only', null);
        $cacheExcept = isset($this->cacheExcept) ? $this->cacheExcept : config('repositories.cache.allowed.except',
            null);

        if (is_array($cacheOnly)) {
            return isset($cacheOnly[$method]);
        }

        if (is_array($cacheExcept)) {
            return !in_array($method, $cacheExcept);
        }

        if (is_null($cacheOnly) && is_null($cacheExcept)) {
            return true;
        }

        return false;
    }

    /**
     * Get Cache key for the method
     *
     * @param $method
     * @param $args
     * @return string
     */
    public function getCacheKey($method, $args = null)
    {
        $args = serialize($args);

        return sprintf('%s@%s-%s',
            get_called_class(),
            $method,
            md5($args) // TODO: Add `scopeQuery` and `with` arrays
        );
    }

    /**
     * Get cache minutes
     *
     * @return int
     */
    public function getCacheMinutes()
    {
        return isset($this->cacheMinutes) ? $this->cacheMinutes : config('repositories.cache.minutes', 30);
    }

    /**
     * Find data by multiple fields
     *
     * @param string $method
     * @param array  $args
     * @return mixed
     */
    public function getCache($method, $args = [])
    {
        if (!$this->allowedCache($method) || $this->isSkippedCache()) {
            return call_user_func_array([$this, $method], $args);
        }

        // Get cache manager
        $cache = app('Illuminate\Cache\CacheManager');

        // Set cache parameters
        $key = $this->getCacheKey($method, $args);
        $time = $this->getCacheMinutes();

        return $cache->tags(get_called_class())->remember($key, $time, function () use ($method, $args) {
            return call_user_func_array([$this, $method], $args);
        });
    }

//    /**
//     * Retrieve all data of repository
//     *
//     * @param array $columns
//     * @return mixed
//     */
//    public function all($columns = ['*'])
//    {
//        return $this->getCache('all', [$columns]);
//    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param  string $method
     * @param  array  $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        // Call cached method
        if (substr($method, 0, 6) === 'cached') {
            return $this->getCache(lcfirst(substr($method, 6)), $args);
        }

        return parent::__call($method, $args);
    }
}