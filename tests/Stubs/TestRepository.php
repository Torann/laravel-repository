<?php

namespace Torann\LaravelRepository\Test\Stubs;

use Mockery;
use Torann\LaravelRepository\Eloquent\Repository;

class TestRepository extends Repository
{
    public $builderMock;

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
