<?php

namespace Torann\LaravelRepository\Repositories;

use Closure;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Torann\LaravelRepository\Cache\CacheInterface;

abstract class AbstractCacheDecorator implements RepositoryInterface
{
    /**
     * Cache expires constants
     */
    const EXPIRES_END_OF_DAY = 'end_of_day';

    /**
     * Repository instance
     *
     * @var RepositoryInterface
     */
    protected $repo;

    /**
     * Methods to skip when caching.
     *
     * @var array
     */
    protected $skipCache = [
        'find',
        'findBy',
        'findAllBy',
        'findWhere',
        'all',
        'pluck',
        'paginate',
    ];

    /**
     * Cache instance
     *
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Lifetime of the cache.
     *
     * @var int
     */
    protected $cacheMinutes = 60;

    /**
     * Create a new cache decorator instance
     *
     * @param RepositoryInterface $repo
     * @param CacheInterface      $cache
     */
    public function __construct(RepositoryInterface $repo, CacheInterface $cache)
    {
        $this->repo = $repo;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function getModel()
    {
        return $this->repo->getModel();
    }

    /**
     * {@inheritdoc}
     */
    public function find($id, $columns = ['*'])
    {
        return $this->getCache('find', func_get_args(), function () use ($id, $columns) {
            return $this->repo->find($id, $columns);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function findBy($attribute, $value, $columns = ['*'])
    {
        return $this->getCache('findBy', func_get_args(), function () use ($attribute, $value, $columns) {
            return $this->repo->findBy($attribute, $value, $columns);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function findAllBy($attribute, $value, $columns = ['*'])
    {
        return $this->getCache('findAllBy', func_get_args(), function () use ($attribute, $value, $columns) {
            return $this->repo->findAllBy($attribute, $value, $columns);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function findWhere(array $where, $columns = ['*'])
    {
        return $this->getCache('findWhere', func_get_args(), function () use ($where, $columns) {
            return $this->repo->findWhere($where, $columns);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function all($columns = ['*'])
    {
        return $this->getCache('all', func_get_args(), function () use ($columns) {
            return $this->repo->all($columns);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function pluck($value, $key = null)
    {
        return $this->getCache('pluck', func_get_args(), function () use ($value, $key) {
            return $this->repo->pluck($value, $key);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function paginate($limit = null, $columns = ['*'])
    {
        return $this->getCache('paginate', func_get_args(), function () use ($limit, $columns) {
            return $this->repo->paginate($limit, $columns);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $attributes)
    {
        if ($entry = $this->repo->create($attributes)) {
            $this->cache->fire('create', $this->repo);
        }

        return $entry;
    }

    /**
     * {@inheritdoc}
     */
    public function update(Model $entity, array $attributes)
    {
        if ($result = $this->repo->update($entity, $attributes)) {
            $this->cache->fire('update', $this->repo);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity)
    {
        if ($result = $this->repo->delete($entity)) {
            $this->cache->fire('delete', $this->repo);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function with(array $relations)
    {
        return $this->repo->with($relations);
    }

    /**
     * {@inheritdoc}
     */
    public function toSql()
    {
        return $this->repo->toSql();
    }

    /**
     * {@inheritdoc}
     */
    public function addError($message)
    {
        return $this->repo->addError($message);
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors()
    {
        return $this->repo->getErrors();
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorMessage($default = '')
    {
        return $this->repo->getErrorMessage($default);
    }

    /**
     * Determine if the cache will be skipped
     *
     * @param string $method
     *
     * @return bool
     */
    public function isSkippedCache($method)
    {
        return in_array($method, $this->skipCache)
            || app('request')->get(config('repositories.cache.skipParam', 'skipCache'));
    }

    /**
     * Get Cache key for the method
     *
     * @param  string $method
     * @param  mixed  $args
     *
     * @return string
     */
    public function getCacheKey($method, $args = null)
    {
        $args = serialize($args)
            . serialize($this->repo->getScopeQuery())
            . serialize($this->repo->getWith());

        return sprintf('%s-%s@%s-%s',
            config('app.locale'),
            get_called_class(),
            $method,
            md5($args)
        );
    }

    /**
     * Get an item from the cache, or store the default value.
     *
     * @param  string  $method
     * @param  array   $args
     * @param  Closure $callback
     * @param  int     $time
     *
     * @return mixed
     */
    public function getCache($method, array $args = [], Closure $callback, $time = null)
    {
        if ($this->isSkippedCache($method)) {
            return $callback($this);
        }

        return $this->cache->remember(
            $this->getCacheKey($method, $args),
            $callback,
            $this->getCacheExpiresTime($time)
        );
    }

    /**
     * Return the time until expires in minutes.
     *
     * @param int $time
     *
     * @return int
     */
    protected function getCacheExpiresTime($time = null)
    {
        if ($time === self::EXPIRES_END_OF_DAY) {
            return class_exists(Carbon::class)
                ? round(Carbon::now()->secondsUntilEndOfDay() / 60)
                : $this->cacheMinutes;
        }

        return $this->cacheMinutes;
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param  string $method
     * @param  array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->repo, $method], $parameters);
    }
}