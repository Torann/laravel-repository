<?php

namespace Torann\LaravelRepository\Eloquent;

use Closure;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Torann\LaravelRepository\Events\RepositoryEntityEvent;
use Torann\LaravelRepository\Contracts\RepositoryInterface;
use Torann\LaravelRepository\Exceptions\RepositoryException;

abstract class Repository implements RepositoryInterface
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $eloquentModel;

    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $query;

    /**
     * Global query scope.
     *
     * @var array
     */
    protected $scopeQuery = [];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = [];

    /**
     * The relations to eager load on every query.
     *
     * @var \Illuminate\Cache\CacheManager
     */
    protected $cache;

    /**
     * Lifetime of the cache.
     *
     * @var int
     */
    protected $cacheMinutes = 30;

    /**
     * Skip cache.
     *
     * @var int
     */
    protected $cacheSkip = false;

    /**
     * Method to include in caching.
     *
     * @var array
     */
    protected $cacheOnly = [];

    /**
     * Method to exclude from caching.
     *
     * @var array
     */
    protected $cacheExcept = [];

    /**
     * Create a new Repository instance
     *
     * @param  CacheManager $cache
     * @throws RepositoryException
     */
    public function __construct(CacheManager $cache)
    {
        $this->cache = $cache;

        $this->makeModel();
        $this->scopeReset();
        $this->boot();
    }

    /**
     * Reset internal Query
     *
     * @return $this
     */
    public function scopeReset()
    {
        $this->scopeQuery = [];

        $this->query = $this->newQuery();

        return $this;
    }

    /**
     * The "booting" method of the repository.
     */
    public function boot()
    {
        //
    }

    /**
     * Get a new entity instance
     *
     * @param  array $attributes
     * @return  \Illuminate\Database\Eloquent\Model
     */
    public function getNew(array $attributes = [])
    {
        return $this->eloquentModel->newInstance($attributes);
    }

    /**
     * Get a new query builder instance
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function newQuery()
    {
        $this->query = $this->getNew()->newQuery();

//        $this->applyEagerLoads();
        $this->applyScope();

        return $this;
    }

    /**
     * Find data by id
     *
     * @param  mixed $id
     * @param  array $columns
     * @return Model|Collection
     */
    public function find($id, $columns = ['*'])
    {
        $this->newQuery();

        return $this->query->find($id, $columns);
    }

    /**
     * Find data by field and value
     *
     * @param       $field
     * @param       $value
     * @param array $columns
     * @return Model|Collection
     */
    public function findBy($field, $value, $columns = ['*'])
    {
        $this->newQuery();

        return $this->query->where($field, '=', $value)->first();
    }

    /**
     * Find data by field
     *
     * @param mixed $attribute
     * @param mixed $value
     * @param array $columns
     * @return mixed
     */
    public function findAllBy($attribute, $value, $columns = ['*'])
    {
        $this->newQuery();

        // Perform where in
        if (is_array($value)) {
            return $this->query->whereIn($attribute, $value)->get($columns);
        }

        return $this->query->where($attribute, '=', $value)->get($columns);
    }

    /**
     * Find data by multiple fields
     *
     * @param array $where
     * @param array $columns
     * @return mixed
     */
    public function findWhere(array $where, $columns = ['*'])
    {
        $this->newQuery();

        foreach ($where as $field => $value) {
            if (is_array($value)) {
                list($field, $condition, $val) = $value;
                $this->query->where($field, $condition, $val);
            }
            else {
                $this->query->where($field, '=', $value);
            }
        }

        return $this->query->get($columns);
    }

    /**
     * Retrieve all data of repository
     *
     * @param array $columns
     * @return Collection
     */
    public function all($columns = ['*'])
    {
        $this->newQuery();

        return $this->query->get($columns);
    }

    /**
     * Get an array with the values of a given column.
     *
     * @param  string $value
     * @param  string $key
     * @return array
     */
    public function lists($value, $key = null)
    {
        $this->newQuery();

        $lists = $this->query->lists($value, $key);

        if (is_array($lists)) {
            return $lists;
        }

        return $lists->all();
    }

    /**
     * Retrieve all data of repository, paginated
     * @param null  $limit
     * @param array $columns
     * @return Paginator
     */
    public function paginate($limit = null, $columns = ['*'])
    {
        $this->newQuery();

        $limit = is_null($limit) ? config('repositories.pagination.limit', 15) : $limit;

        return $this->query->paginate($limit, $columns);
    }

    /**
     * Save a new entity in repository
     *
     * @param array $attributes
     * @return Model
     */
    public function create(array $attributes)
    {
        $model = $this->getNew()->create($attributes);

        if ($model) {
            event(new RepositoryEntityEvent('create', $this));
        }

        return $model;
    }

    /**
     * Update an entity with the given attributes and persist it
     *
     * @param  Model $entity
     * @param  array $attributes
     * @return bool
     */
    public function update(Model $entity, array $attributes)
    {
        $result = $entity->update($attributes);

        if ($result) {
            event(new RepositoryEntityEvent('update', $this));
        }

        return $result;
    }

    /**
     * Delete a entity in repository
     *
     * @param  mixed $entry
     * @return int
     */
    public function delete($entry)
    {
        if (($entry instanceof Model) === false) {
            $entity = $this->find($entry);
        }

        $result = $entity->delete();

        if ($result) {
            event(new RepositoryEntityEvent('delete', $this));
        }

        return $result;
    }

    /**
     * Load relations
     *
     * @param array $relations
     * @return $this
     */
    public function with(array $relations)
    {
        $this->with[] = $relations;

        return $this;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws RepositoryException
     */
    public function makeModel()
    {
        if (!$this->model) {
            throw new RepositoryException("The model class must be set on the repository.");
        }

        return $this->eloquentModel = with(new $this->model);
    }

    /**
     * Add query scope.
     *
     * @param Closure $scope
     * @return $this
     */
    public function addScopeQuery(Closure $scope)
    {
        $this->scopeQuery[] = $scope;

        return $this;
    }

    /**
     * Apply scope in current Query
     *
     * @return $this
     */
    protected function applyScope()
    {
        foreach ($this->scopeQuery as $callback) {
            if (is_callable($callback)) {
                $this->query = $callback($this->query);
            }
        }

        // Clear scopes
        $this->scopeQuery = [];

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
            return true;
        }

        // Check repository for caching
        $skipped = isset($this->cacheSkip) ? $this->cacheSkip : false;

        // Check request for cache override
        if (request(config('repositories.cache.skipParam', 'skipCache'))) {
            $skipped = true;
        }

        return $skipped;
    }

    /**
     * Determine if method should be cached
     *
     * @param $method
     * @return bool
     */
    protected function allowedCache($method)
    {
        // Globally turned off
        if (config('repositories.cache.enabled', false) === false) {
            return false;
        }

        // Only cache certain methods
        if (in_array($method, $this->cacheOnly) === true) {
            return true;
        }

        // Cache everything except given methods
        if (in_array($method, $this->cacheExcept) === false) {
            return ! in_array($method, $this->cacheExcept);
        }

        return (empty($this->cacheOnly) && empty($this->cacheExcept));
    }

    /**
     * Get Cache key for the method
     *
     * @param  string $method
     * @param  mixed  $args
     * @return string
     */
    public function getCacheKey($method, $args = null)
    {
        $args = serialize($args)
            . serialize($this->scopeQuery)
            . serialize($this->with);

        return sprintf('%s@%s-%s',
            get_called_class(),
            $method,
            md5($args)
        );
    }

    /**
     * Get an item from the cache, or store the default value.
     *
     * @param  string $method
     * @param  array  $args
     * @param  Closure $callback
     * @param  int $time
     * @return mixed
     */
    public function getCache($method, array $args = [], Closure $callback, $time = null)
    {
        if ($this->isSkippedCache()) {
            return $callback($this);
        }

        // Set cache parameters
        $key = $this->getCacheKey($method, $args);
        $time = $time ?: $this->cacheMinutes;

        return $this->cache->tags(get_called_class())->remember($key, $time, function () use ($callback) {
            return $callback($this);
        });
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param  string $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // Check for scope method and call
        if (method_exists($this, $scope = 'scope' . ucfirst($method))) {
            return call_user_func_array([$this, $scope], $parameters) ?: $this;
        }

        return $this;
    }
}