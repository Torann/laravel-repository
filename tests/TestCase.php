<?php

namespace Torann\LaravelRepository\Test;

use Mockery;
use PHPUnit_Framework_TestCase;

class TestCase extends PHPUnit_Framework_TestCase
{
    public static $functions;

    public function setUp()
    {
        self::$functions = Mockery::mock();

        // Cache Mock
        $cacheMock = Mockery::mock('Illuminate\Cache\CacheManager');
        self::$functions->shouldReceive('app')->with('cache', null)->andReturn($cacheMock);

        // Request mock
        $requestMock = Mockery::mock('Illuminate\Http\Request');
        $requestMock->shouldReceive('get')->andReturn(null);
        self::$functions->shouldReceive('app')->with('request', null)->andReturn($requestMock);
    }

    public function tearDown()
    {
        Mockery::close();
    }

    protected function makeRepository()
    {
        return new \Torann\LaravelRepository\Test\Stubs\TestRepository();
    }

    protected function makeCacheDecorator()
    {
        $repo = $this->makeRepository();

        $cache = Mockery::mock('Torann\LaravelRepository\Cache\CacheInterface');

        $cache->shouldReceive('setMinutes')->andReturn(90);

        return new \Torann\LaravelRepository\Test\Stubs\TestCacheDecorator($repo, $cache);
    }

    protected function makeLaravelCache()
    {
        return new \Torann\LaravelRepository\Cache\LaravelCache(['test']);
    }

    public function makeMockQuery()
    {
        return Mockery::mock('Illuminate\Database\Eloquent\Builder');
    }
}