<?php

namespace Torann\LaravelRepository\Events;

use Illuminate\Database\Eloquent\Model;
use Torann\LaravelRepository\Cache\CacheInterface;

interface RepositoryEventInterface
{
    /**
     * Create new event instance.
     *
     * @param string         $action
     * @param CacheInterface $cache
     */
    public function __construct($action, CacheInterface $cache);

    /**
     * Return cache instance.
     *
     * @return CacheInterface
     */
    public function getCache();

    /**
     * Return event action name.
     *
     * @return string
     */
    public function getAction();
}