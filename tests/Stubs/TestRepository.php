<?php

namespace Torann\LaravelRepository\Test\Stubs;

use Mockery;
use Torann\LaravelRepository\Repositories\AbstractRepository;
use Torann\LaravelRepository\Repositories\RepositoryInterface;

class TestRepository extends AbstractRepository implements RepositoryInterface
{
    public $builderMock;

    protected $authorization = [];

    public function makeModel()
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
        return $this->addScopeQuery(function($query) {
            return $query->where('is_admin', true);
        });
    }
}
