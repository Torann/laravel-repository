<?php

namespace Torann\LaravelRepository\Cache;

use Closure;
use Torann\LaravelRepository\Events\RepositoryEvent;
use Torann\LaravelRepository\Repositories\RepositoryInterface;

class LaravelCache implements CacheInterface
{
    /**
     * Instance of cache manager.
     *
     * @var \Illuminate\Cache\CacheManager
     */
    protected $cache;

    /**
     * The cache event to fire.
     *
     * @var string
     */
    protected $cacheEvent = RepositoryEvent::class;

    /**
     * Lifetime of the cache.
     *
     * @var int
     */
    protected $minutes = 60;

    /**
     * Array of cache tags
     *
     * @var array
     */
    protected $tags = [];

    /**
     * Create a new laravel cache instance.
     *
     * @param array|string $tags
     * @param int          $minutes
     */
    public function __construct($tags, $minutes = null)
    {
        $this->cache = app('cache');
        $this->tags = is_array($tags) ? $tags : [$tags];
        $this->cacheEvent = config('repositories.cache.clean_event', $this->cacheEvent);

        $this->minutes = $minutes !== null ? $minutes : $this->minutes;
    }

    /**
     * Get cache minutes.
     *
     * @return int
     */
    public function getMinutes()
    {
        return $this->minutes;
    }

    /**
     * Set cache minutes.
     *
     * @param int $minutes
     */
    public function setMinutes($minutes)
    {
        $this->minutes = $minutes;
    }

    /**
     * Fire repository event.
     *
     * @param  string              $event
     * @param  RepositoryInterface $repo
     */
    public function fire($event, RepositoryInterface $repo)
    {
        event(new $this->cacheEvent($event, $this));
    }

    /**
     * Get an item from the cache, or store the value.
     *
     * @param  string  $key
     * @param  Closure $callback
     * @param  int     $minutes
     *
     * @return mixed
     */
    public function remember($key, Closure $callback, $minutes = null)
    {
        if (is_null($minutes)) {
            $minutes = $this->minutes;
        }

        return $this->cache->tags($this->tags)->remember($key, $minutes, function () use ($callback) {
            return $callback();
        });
    }

    /**
     * Get tags.
     *
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set tags.
     *
     * @param  array $tags
     *
     * @return void
     */
    public function addTags($tags)
    {
        $tags = is_array($tags) ? $tags : func_get_args();

        $this->tags = array_merge($this->tags, $tags);
    }

    /**
     * Flush cache for tags.
     *
     * @param  mixed $tags
     *
     * @return bool
     */
    public function flush($tags = null)
    {
        if ($tags !== null) {
            $tags = is_array($tags) ? $tags : func_get_args();
        }
        else {
            $tags = $this->tags;
        }

        return $this->cache->tags($tags)->flush();
    }
}