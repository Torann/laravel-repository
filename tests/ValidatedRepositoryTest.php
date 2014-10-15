<?php namespace Torann\LaravelRepository\Tests;

use PHPUnit_Framework_TestCase;
use Mockery as m;

class ValidatedRepositoryTest extends PHPUnit_Framework_TestCase
{
    protected function getRepo()
    {
        $class = __NAMESPACE__ . '\\RepositoryStub';
        $model = m::mock('Illuminate\Database\Eloquent\Model');
        $model->shouldReceive('newInstance')->once()->with([])->andReturn($model);
        $model->shouldReceive('getKeyName')->andReturn('id');
        $validator = m::mock('Torann\LaravelRepository\AbstractValidator');
        return new $class($model, $validator);
    }

    /** @test */
    public function errorsAreAddedAndCanBeRetrieved()
    {
        $repo = $this->getRepo();
        $repo->getValidator()->shouldReceive('validate')->once()->with('create', ['foo' => 'bar'])->andReturn(false);
        $repo->getValidator()->shouldReceive('getErrors')->once()->andReturn(new \Illuminate\Support\MessageBag(['error' => ['message']]));
        $repo->create(['foo' => 'bar']);
        $errors = $repo->getErrors();
        $this->assertInstanceOf('Illuminate\Support\MessageBag', $errors);
        $this->assertEquals(['error' => ['message']], $errors->getMessages());
    }

    /** @test */
    public function modelIsValidated()
    {
        $repo = $this->getRepo();
        $model = m::mock('Illuminate\Database\Eloquent\Model');
        $model->exists = true;
        $model->shouldReceive('getKeyName')->andReturn('id');
        $model->shouldReceive('getKey')->once()->andReturn('1');
        $model->shouldReceive('getAttribute')->once()->andReturn(1);
        $repo->getValidator()->shouldReceive('validate')->once()->with('update', ['foo' => 'bar', 'id' => 1])->andReturn(false);
        $repo->getValidator()->shouldReceive('getErrors')->once()->andReturn([]);
        $this->assertFalse($repo->update($model, ['foo' => 'bar']));
    }
}

class RepositoryStub extends \Torann\LaravelRepository\EloquentRepository {}
