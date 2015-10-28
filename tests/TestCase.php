<?php
namespace Torann\LaravelRepository;

use Mockery;
use PHPUnit_Framework_TestCase;
use Illuminate\Support\Collection;

class TestCase extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    protected function make()
    {
        $mock = Mockery::mock('Illuminate\Support\Collection');
        $mock->shouldReceive('getIterator')->andReturn(new \ArrayIterator([]));

        $repo = new \Torann\LaravelRepository\Stubs\TestRepository($mock);

        $repo->setModel($this->makeMockModel());

        return $repo;
    }

    public function makeMockModel()
    {
        $mock = Mockery::mock('Illuminate\Database\Eloquent\Model');
        $mock->shouldReceive('getQualifiedKeyName')->andReturn('table.id');
        $mock->shouldReceive('getKeyName')->andReturn('id');

        return $mock;
    }

    public function makeMockQuery()
    {
        return Mockery::mock('Illuminate\Database\Eloquent\Builder');
    }
}
