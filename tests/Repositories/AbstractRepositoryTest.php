<?php

namespace Torann\LaravelRepository\Test\Repositories;

use Torann\LaravelRepository\Test\TestCase;

class AbstractRepositoryTest extends TestCase
{
    /**
     * @test
     */
    public function preventInvalidUserInput()
    {
        $repo = $this->makeRepository();

        $repo->builderMock
            ->shouldReceive('paginate')->once()
            ->with(15, ['*'], 'page', 0)
            ->andReturn(true);

        // Ensure the package casts the per page limit correctly and returns the default 15
        $this->assertEquals(true, $repo->paginate('select *'));
    }

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
        $expected_array = [
            [
                'title' => 'admin',
                'name' => 'Bill',
            ],
            [
                'title' => 'user',
                'name' => 'Kelly',
            ],
        ];

        $repo = $this->makeRepository();

        $repo->builderMock
            ->shouldReceive('pluck')->once()
            ->andReturn($expected_array);

        $this->assertEquals($expected_array, $repo->pluck('title', 'name'));
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
        $expected_array = [
            'id' => 123,
            'email' => 'admin@mail.com',
            'name' => 'Bill',
        ];

        $repo = $this->makeRepository();

        $repo->builderMock
            ->shouldReceive('find')->once()
            ->andReturn($expected_array);

        $this->assertEquals($expected_array, $repo->find($expected_array['id']));
    }

    /**
     * @test
     */
    public function testFindBy()
    {
        $expected_array = [
            'id' => 123,
            'email' => 'admin@mail.com',
            'name' => 'Bill',
        ];

        $repo = $this->makeRepository();
        $query = $this->makeMockQuery();

        $repo->builderMock
            ->shouldReceive('where')->once()
            ->with('id', '=', $expected_array['id'])->once()
            ->andReturn($query);

        $query->shouldReceive('first')->once()
            ->andReturn($expected_array);

        $this->assertEquals($expected_array, $repo->findBy('id', $expected_array['id']));
    }

    /**
     * @test
     */
    public function testFindAllBy()
    {
        $expected_array = [
            [
                'id' => 123,
                'email' => 'admin@mail.com',
                'name' => 'Bill',
            ],
            [
                'id' => 124,
                'email' => 'admin@mail.com',
                'name' => 'Todd',
            ],
        ];

        $repo = $this->makeRepository();
        $query = $this->makeMockQuery();

        $repo->builderMock
            ->shouldReceive('where')->once()
            ->with('email', '=', 'admin@mail.com')->once()
            ->andReturn($query);

        $query->shouldReceive('get')->once()
            ->andReturn($expected_array);

        $this->assertEquals($expected_array, $repo->findAllBy('email', 'admin@mail.com'));
    }

    /**
     * @test
     */
    public function testFindAllByArray()
    {
        $ids = [1, 33];

        $expected_array = [
            [
                'id' => 1,
                'email' => 'admin@mail.com',
                'name' => 'Bill',
            ],
            [
                'id' => 33,
                'email' => 'admin@mail.com',
                'name' => 'Todd',
            ],
        ];

        $repo = $this->makeRepository();
        $query = $this->makeMockQuery();

        $repo->builderMock
            ->shouldReceive('whereIn')->once()
            ->with('id', $ids)->once()
            ->andReturn($query);

        $query->shouldReceive('get')->once()
            ->andReturn($expected_array);

        $this->assertEquals($expected_array, $repo->findAllBy('id', $ids));
    }

    /**
     * @test
     */
    public function testFindWhere()
    {
        $expected_array = [
            [
                'id' => 123,
                'email' => 'admin@mail.com',
                'name' => 'Bill',
            ],
        ];

        $repo = $this->makeRepository();

        $repo->builderMock
            ->shouldReceive('where')->once()
            ->with('id', '=', 123)->once()
            ->shouldReceive('get')->once()
            ->andReturn($expected_array);

        $this->assertEquals($expected_array, $repo->findWhere([
            'id' => 123,
        ]));
    }

    /**
     * @test
     */
    public function testFindWhereWithConditions()
    {
        $expected_array = [
            [
                'id' => 123,
                'email' => 'admin@mail.com',
                'name' => 'Bill',
            ],
        ];

        $repo = $this->makeRepository();

        $repo->builderMock
            ->shouldReceive('where')->once()
            ->with('id', '<', 123)->once()
            ->shouldReceive('get')->once()
            ->andReturn($expected_array);

        $this->assertEquals($expected_array, $repo->findWhere([
            ['id', '<', 123],
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
        $expected_array = [
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
            ],
        ];

        $repo = $this->makeRepository();
        $query = $this->makeMockQuery();

        $repo->builderMock
            ->shouldReceive('where')->once()
            ->with('is_admin', true)->once()
            ->andReturn($query);

        $query->shouldReceive('get')->once()
            ->andReturn($expected_array);

        $this->assertEquals($expected_array, $repo->adminOnlyScope()->all());
    }
}
