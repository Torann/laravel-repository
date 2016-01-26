<?php

namespace Torann\LaravelRepository\Events;

use Illuminate\Database\Eloquent\Model;
use Torann\LaravelRepository\Cache\CacheInterface;

class RepositoryEvent implements RepositoryEventInterface
{
    /**
     * Cache instance.
     *
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Event action name.
     *
     * @var string
     */
    protected $action;

    /**
     * Create new event instance.
     *
     * @param string         $action
     * @param CacheInterface $cache
     */
    public function __construct($action, CacheInterface $cache)
    {
        $this->action = $action;
        $this->cache = $cache;
    }

    /**
     * Return cache instance.
     *
     * @return CacheInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Return event action name.
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }
}