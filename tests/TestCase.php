<?php

namespace Torann\LaravelRepository\Test;

use Mockery;
use PHPUnit_Framework_TestCase;

class TestCase extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    protected function make()
    {
        $cacheMock = Mockery::mock('Illuminate\Cache\CacheManager');

        return new \Torann\LaravelRepository\Test\Stubs\TestRepository($cacheMock);
    }

    public function makeMockQuery()
    {
        return Mockery::mock('Illuminate\Database\Eloquent\Builder');
    }
}
