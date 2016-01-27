<?php

namespace Torann\LaravelRepository\Test\Stubs;

use Mockery;
use Torann\LaravelRepository\Repositories\RepositoryInterface;
use Torann\LaravelRepository\Repositories\AbstractCacheDecorator;

class TestCacheDecorator extends AbstractCacheDecorator implements RepositoryInterface
{
    public function getBuilderMock()
    {
        return $this->repo->builderMock;
    }
}
