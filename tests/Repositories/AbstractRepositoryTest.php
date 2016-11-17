<?php

namespace Torann\LaravelRepository\Test\Repositories;

use Torann\LaravelRepository\Test\TestCase;

class AbstractRepositoryTest extends TestCase
{
    protected $hits;

    /**
     * @test
     */
    public function shouldGetAll()
    {
        $repo = $this->makeRepository();

        $repo->builderMock
            ->shouldReceive('get')->once()
            ->andReturn('foo');

        $this->assertEquals('foo', $repo->all());
    }

    /**
     * @test
     */
    public function testPluck()
    {
        $expectedArray = [
            [
                'title' => 'admin',
                'name' => 'Bill',
            ],
            [
                'title' => 'user',
                'name' => 'Kelly',
            ]
        ];

        $repo = $this->makeRepository();

        $repo->builderMock
            ->shouldReceive('pluck')->once()
            ->andReturn($expectedArray);

        $this->assertEquals($expectedArray, $repo->pluck('title', 'name'));
    }

    /**
     * @test
     */
    public function testPaginate()
    {
        $repo = $this->makeRepository();

        $repo->builderMock
            ->shouldReceive('paginate')->once()
            ->andReturn(true);

        $this->assertEquals(true, $repo->paginate(11));
    }

    /**
     * @test
     */
    public function testSimplePaginate()
    {
        $repo = $this->makeRepository();

        $repo->builderMock
            ->shouldReceive('simplePaginate')->once()
            ->andReturn(true);

        $this->assertEquals(true, $repo->simplePaginate(11));
    }

    /**
     * @test
     */
    public function testFind()
    {
        $expectedArray = [
            'id' => 123,
            'email' => 'admin@mail.com',
            'name' => 'Bill',
        ];

        $repo = $this->makeRepository();

        $repo->builderMock
            ->shouldReceive('find')->once()
            ->andReturn($expectedArray);

        $this->assertEquals($expectedArray, $repo->find($expectedArray['id']));
    }

    /**
     * @test
     */
    public function testFindBy()
    {
        $expectedArray = [
            'id' => 123,
            'email' => 'admin@mail.com',
            'name' => 'Bill',
        ];

        $repo = $this->makeRepository();
        $query = $this->makeMockQuery();

        $repo->builderMock
            ->shouldReceive('where')->once()
            ->with('id', '=', $expectedArray['id'])->once()
            ->andReturn($query);

        $query->shouldReceive('first')->once()
            ->andReturn($expectedArray);

        $this->assertEquals($expectedArray, $repo->findBy('id', $expectedArray['id']));
    }

    /**
     * @test
     */
    public function testFindAllBy()
    {
        $expectedArray = [
            [
                'id' => 123,
                'email' => 'admin@mail.com',
                'name' => 'Bill',
            ],
            [
                'id' => 124,
                'email' => 'admin@mail.com',
                'name' => 'Todd',
            ]
        ];

        $repo = $this->makeRepository();
        $query = $this->makeMockQuery();

        $repo->builderMock
            ->shouldReceive('where')->once()
            ->with('email', '=', 'admin@mail.com')->once()
            ->andReturn($query);

        $query->shouldReceive('get')->once()
            ->andReturn($expectedArray);

        $this->assertEquals($expectedArray, $repo->findAllBy('email', 'admin@mail.com'));
    }

    /**
     * @test
     */
    public function testFindAllByArray()
    {
        $ids = [1, 33];

        $expectedArray = [
            [
                'id' => 1,
                'email' => 'admin@mail.com',
                'name' => 'Bill',
            ],
            [
                'id' => 33,
                'email' => 'admin@mail.com',
                'name' => 'Todd',
            ]
        ];

        $repo = $this->makeRepository();
        $query = $this->makeMockQuery();

        $repo->builderMock
            ->shouldReceive('whereIn')->once()
            ->with('id', $ids)->once()
            ->andReturn($query);

        $query->shouldReceive('get')->once()
            ->andReturn($expectedArray);

        $this->assertEquals($expectedArray, $repo->findAllBy('id', $ids));
    }

    /**
     * @test
     */
    public function testFindWhere()
    {
        $expectedArray = [
            [
                'id' => 123,
                'email' => 'admin@mail.com',
                'name' => 'Bill',
            ]
        ];

        $repo = $this->makeRepository();

        $repo->builderMock
            ->shouldReceive('where')->once()
            ->with('id', '=', 123)->once()
            ->shouldReceive('get')->once()
            ->andReturn($expectedArray);

        $this->assertEquals($expectedArray, $repo->findWhere([
            'id' => 123
        ]));
    }

    /**
     * @test
     */
    public function testFindWhereWithConditions()
    {
        $expectedArray = [
            [
                'id' => 123,
                'email' => 'admin@mail.com',
                'name' => 'Bill',
            ]
        ];

        $repo = $this->makeRepository();

        $repo->builderMock
            ->shouldReceive('where')->once()
            ->with('id', '<', 123)->once()
            ->shouldReceive('get')->once()
            ->andReturn($expectedArray);

        $this->assertEquals($expectedArray, $repo->findWhere([
            ['id', '<', 123]
        ]));
    }

    /**
     * @test
     */
    public function testCacheCallbackWithCache()
    {
        $repo = $this->makeRepository();

        $repo->skipCacheCheck = true;

        $cache = app('cache');

        $cache->shouldReceive('tags')->once()
            ->with(['repositories', 'Torann\\LaravelRepository\\Test\\Stubs\\TestRepository'])->once()
            ->andReturnSelf();

        $cache->shouldReceive('remember')->once()
            ->andReturn('admin@mail.com');

        $repo::setCacheInstance($cache);

        $this->assertEquals('admin@mail.com', $repo->findByEmail('admin@mail.com'));
    }

    /**
     * @test
     */
    public function testFindUsingScope()
    {
        $expectedArray = [
            [
                'id' => 123,
                'email' => 'admin@mail.com',
                'name' => 'Bill',
                'is_admin' => true,
            ],
            [
                'id' => 33,
                'email' => 'admin@mail.com',
                'name' => 'Todd',
                'is_admin' => true,
            ]
        ];

        $repo = $this->makeRepository();
        $query = $this->makeMockQuery();

        $repo->builderMock
            ->shouldReceive('where')->once()
            ->with('is_admin', true)->once()
            ->andReturn($query);

        $query->shouldReceive('get')->once()
            ->andReturn($expectedArray);

        $this->assertEquals($expectedArray, $repo->adminOnlyScope()->all());
    }
}
