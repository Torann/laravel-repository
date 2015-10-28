<?php
namespace Torann\LaravelRepository\Eloquent\Repository;

use Mockery;
use Torann\LaravelRepository\TestCase;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class RepositoryTest extends TestCase
{
    protected $hits;

    /**
     * @test
     */
    public function shouldGetAll()
    {
        $repo = $this->make();

        $repo->getModel()
            ->shouldReceive('get')
            ->once()
            ->andReturn('foo');

        $this->assertEquals('foo', $repo->all());
    }

    /**
     * @test
     */
    public function testLists()
    {
        $expectedArray = [
            [
                'title' => 'admin',
                'name' => 'Bill'
            ],
            [
                'title' => 'user',
                'name' => 'Kelly'
            ]
        ];

        $repo = $this->make();

        $repo->getModel()
            ->shouldReceive('lists')
            ->once()
            ->andReturn($expectedArray);

        $this->assertEquals($expectedArray, $repo->lists('title', 'name'));
    }

    /**
     * @test
     */
    public function testPaginate()
    {
        $repo = $this->make();

        $repo->getModel()
            ->shouldReceive('paginate')
            ->once()
            ->andReturn(true);

        $this->assertEquals(true, $repo->paginate(11));
    }

    /**
     * @test
     */
    public function testFind()
    {
        $expectedArray = [
            'id' => 123,
            'email' => 'admin@mail.com',
            'name' => 'Bill'
        ];

        $repo = $this->make();

        $repo->getModel()
            ->shouldReceive('find')
            ->once()
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
            'name' => 'Bill'
        ];

        $repo = $this->make();
        $query = $this->makeMockQuery();

        $repo->getModel()
            ->shouldReceive('where')->once()->with('id', '=', 123)->once()->andReturn($query);

        $query->shouldReceive('first')->once()->andReturn($expectedArray);

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
                'name' => 'Bill'
            ],
            [
                'id' => 124,
                'email' => 'admin@mail.com',
                'name' => 'Todd'
            ]
        ];

        $repo = $this->make();
        $query = $this->makeMockQuery();

        $repo->getModel()
            ->shouldReceive('where')->once()->with('email', '=', 'admin@mail.com')->once()->andReturn($query);

        $query->shouldReceive('get')->once()->andReturn($expectedArray);

        $this->assertEquals($expectedArray, $repo->findAllBy('email', 'admin@mail.com'));
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
                'name' => 'Bill'
            ]
        ];

        $repo = $this->make();
        $query = $this->makeMockQuery();

        $repo->getModel()
            ->shouldReceive('where')->once()->with('id', '=', 123)->once()->andReturn($query);

        $query->shouldReceive('get')->once()->andReturn($expectedArray);

        $this->assertEquals($expectedArray, $repo->findWhere([
            'id' => 123
        ]));
    }
}
