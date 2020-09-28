<?php

namespace Torann\LaravelRepository\Test;

use Mockery;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase
{
    public static $functions;

    public function setUp(): void
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

    public function tearDown(): void
    {
        Mockery::close();
    }

    protected function makeRepository(): Stubs\TestRepository
    {
        return new \Torann\LaravelRepository\Test\Stubs\TestRepository();
    }

    public function makeMockQuery()
    {
        return Mockery::mock('Illuminate\Database\Eloquent\Builder');
    }
}