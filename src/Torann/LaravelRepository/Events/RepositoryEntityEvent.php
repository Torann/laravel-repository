<?php

namespace Torann\LaravelRepository\Events;

use Illuminate\Database\Eloquent\Model;
use Torann\LaravelRepository\Contracts\RepositoryInterface;

class RepositoryEntityEvent
{
    /**
     * Repository class name.
     *
     * @var string
     */
    protected $repository;

    /**
     * Event action name.
     *
     * @var string
     */
    protected $action;

    /**
     * @param string $action
     * @param RepositoryInterface $repository
     */
    public function __construct($action, RepositoryInterface $repository)
    {
        $this->action = $action;
        $this->repository = get_class($repository);
    }

    /**
     * Return repository class name.
     *
     * @return string
     */
    public function getRepository()
    {
        return $this->repository;
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