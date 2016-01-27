<?php

namespace Torann\LaravelRepository\Test\Repositories;

use Torann\LaravelRepository\Test\TestCase;

class AbstractCacheDecoratorTest extends TestCase
{
    /**
     * @test
     */
    public function shouldGetCacheKey()
    {
        $decorator = $this->makeCacheDecorator();

        $this->assertEquals('-Torann\LaravelRepository\Test\Stubs\TestCacheDecorator@all-c0996933e4b2f41e49b484fdbbf3069d', $decorator->getCacheKey('all', [
            'limit' => 10,
        ]));
    }
}