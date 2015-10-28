<?php

namespace Torann\LaravelRepository\Traits;

use Closure;

trait CacheableRepository
{
    /**
     * Skip Cache
     *
     * @param bool $status
     * @return $this
     */
    public function skipCache($status = true)
    {
        $this->cacheSkip = $status;

        return $this;
    }

    /**
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
        if (app('Illuminate\Http\Request')->get(config('repositories.cache.params.skipCache', 'skipCache'))) {
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
        $cacheEnabled = config('repositories.cache.enabled', true);

        if (! $cacheEnabled) {
            return false;
        }

        $cacheOnly = isset($this->cacheOnly) ? $this->cacheOnly : config('repositories.cache.allowed.only', null);
        $cacheExcept = isset($this->cacheExcept) ? $this->cacheExcept : config('repositories.cache.allowed.except', null);

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
     * Get an item from the cache, or store the default value.
     *
     * @param  string $method
     * @param  array|null $args
     * @param  \Closure $callback
     * @return mixed
     */
    public function getCache($method, $args = null, Closure $callback)
    {
        return app('Illuminate\Cache\CacheManager')->tags(get_called_class())->remember(
            $this->getCacheKey($method, $args),
            $this->getCacheMinutes(),
            $callback
        );
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
            md5($args)
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
     * Retrieve all data of repository
     *
     * @param array $columns
     * @return mixed
     */
    public function all($columns = ['*'])
    {
        if (!$this->allowedCache('all') || $this->isSkippedCache()) {
            return parent::all($columns);
        }

        return $this->getCache('all', func_get_args(), function () use ($columns) {
            return parent::all($columns);
        });
    }

    /**
     * @param  string $value
     * @param  string $key
     * @return array
     */
    public function lists($value, $key = null)
    {
        if (!$this->allowedCache('lists') || $this->isSkippedCache()) {
            return parent::lists($value, $key);
        }

        return $this->getCache('lists', func_get_args(), function () use ($value, $key) {
            return parent::lists($value, $key);
        });
    }

    /**
     * Retrieve all data of repository, paginated
     * @param null  $limit
     * @param array $columns
     * @return mixed
     */
    public function paginate($limit = null, $columns = ['*'])
    {
        if (!$this->allowedCache('paginate') || $this->isSkippedCache()) {
            return parent::paginate($limit, $columns);
        }

        return $this->getCache('paginate', func_get_args(), function () use ($limit, $columns) {
            return parent::paginate($limit, $columns);
        });
    }

    /**
     * Find data by id
     *
     * @param       $id
     * @param array $columns
     * @return mixed
     */
    public function find($id, $columns = ['*'])
    {
        if (!$this->allowedCache('find') || $this->isSkippedCache()) {
            return parent::find($id, $columns);
        }

        return $this->getCache('find', func_get_args(), function () use ($id, $columns) {
            return parent::find($id, $columns);
        });
    }

    /**
     * @param       $attribute
     * @param       $value
     * @param array $columns
     * @return mixed
     */
    public function findBy($attribute, $value, $columns = ['*'])
    {
        if (!$this->allowedCache('findBy') || $this->isSkippedCache()) {
            return parent::findBy($attribute, $value, $columns);
        }

        return $this->getCache('findBy', func_get_args(), function () use ($attribute, $value, $columns) {
            return parent::findBy($attribute, $value, $columns);
        });
    }

    /**
     * @param       $attribute
     * @param       $value
     * @param array $columns
     * @return mixed
     */
    public function findAllBy($attribute, $value, $columns = ['*'])
    {
        if (!$this->allowedCache('findBy') || $this->isSkippedCache()) {
            return parent::findAllBy($attribute, $value, $columns);
        }

        return $this->getCache('findBy', func_get_args(), function () use ($attribute, $value, $columns) {
            return parent::findAllBy($attribute, $value, $columns);
        });
    }

    /**
     * Find data by multiple fields
     *
     * @param array $where
     * @param array $columns
     * @param bool  $or
     * @return mixed
     */
    public function findWhere(array $where, $columns = ['*'], $or = false)
    {
        if (!$this->allowedCache('findWhere') || $this->isSkippedCache()) {
            return parent::findWhere($where, $columns);
        }

        return $this->getCache('findWhere', func_get_args(), function () use ($where, $columns) {
            return parent::findWhere($where, $columns);
        });
    }
}