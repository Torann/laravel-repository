<?php

namespace Torann\LaravelRepository\Test\Stubs;

use Mockery;
use Illuminate\Database\Eloquent\Model;
use Torann\LaravelRepository\Repository;

class TestRepository extends Repository
{
    public $builderMock;

    protected $authorization = [];

    public $skipCacheCheck = false;

    public function makeModel(): Model
    {
        $this->builderMock = Mockery::mock('Illuminate\Database\Eloquent\Builder');

        $newInstanceMock = Mockery::mock('Illuminate\Database\Eloquent\Model');
        $newInstanceMock->shouldReceive('newQuery')->andReturn($this->builderMock);

        $mock = Mockery::mock('Illuminate\Database\Eloquent\Model');
        $mock->shouldReceive('newInstance')->andReturn($newInstanceMock);

        return $this->modelInstance = $mock;
    }

    public function scopeAdminOnlyScope()
    {
        return $this->addScopeQuery(function ($query) {
            return $query->where('is_admin', true);
        });
    }

    public function skippedCache(): bool
    {
        if ($this->skipCacheCheck === true) return false;

        return parent::skippedCache();
    }

    public function findByEmail($email)
    {
        return $this->cacheCallback(__FUNCTION__, func_get_args(), function () use ($email) {
            return $this->query->where('email', $email);
        });
    }
}
