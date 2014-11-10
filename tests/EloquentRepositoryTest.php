<?php namespace Torann\LaravelRepository\Tests;

use Mockery as m;
use PHPUnit_Framework_TestCase;

class EloquentRepositoryTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    /** @test */
    public function initialize()
    {
        $m = $this->makeMockModel();
        $v = $this->makeMockValidator();
        $r = $this->makeRepo($m, $v);

        $this->assertInstanceOf('Torann\LaravelRepository\EloquentRepository', $r);
    }

    /** @test */
    public function getAll()
    {
        list($model, $validator, $repo) = $this->make();

        $query = $this->makeMockQuery();
        $model->shouldReceive('newQuery')->once()->andReturn($query);
        $query->shouldReceive('get')->once()->andReturn('foo');

        $this->assertEquals('foo', $repo->getAll());
    }

    /** @test */
    public function getManyIn()
    {
        list($model, $validator, $repo) = $this->make();

        $query = $this->makeMockQuery();
        $model->shouldReceive('newQuery')->once()->andReturn($query);
        $query->shouldReceive('whereIn')->with('id', array(1,2,3))->once()->andReturn(m::self());
        $query->shouldReceive('get')->once()->andReturn(array());

        $this->assertEquals(array(), $repo->getManyIn('id', array(1,2,3)));
    }

    /** @test */
    public function getByAttributes()
    {
        list($model, $validator, $repo) = $this->make();

        $query = $this->makeMockQuery();
        $model->shouldReceive('newQuery')->once()->andReturn($query);
        $query->shouldReceive('where')->with('email', '=', 'foo@bar.com')->once()->andReturn(array());
        $query->shouldReceive('get')->once()->andReturn('foo');

        $this->assertEquals('foo', $repo->getByAttributes(array('email' => 'foo@bar.com')));
    }

    /** @test */
    public function getList()
    {
        list($model, $validator, $repo) = $this->make();

        $query = $this->makeMockQuery();
        $model->shouldReceive('newQuery')->once()->andReturn($query);
        $query->shouldReceive('lists')->with('title', 'id')->once()->andReturn(array());

        $this->assertEquals(array(), $repo->getList('title', 'id'));
    }

    /** @test */
    public function getAllPaginated()
    {
        list($model, $validator, $repo) = $this->make();
        $query = $this->makeMockQuery();
        $model->shouldReceive('newQuery')->once()->andReturn($query);
        $query->shouldReceive('paginate')->once()->with(20)->andReturn('foo');

        $this->assertEquals('foo', $repo->paginate(20));
    }

    /** @test */
    public function queryBefore()
    {
        list($model, $validator, $repo) = $this->make(__NAMESPACE__.'\RepoWithBefores');
        $query = $this->makeMockQuery();
        $model->shouldReceive('newQuery')->once()->andReturn($query);
        $query->shouldReceive('doBeforeQueryStuff')->once();
        $query->shouldReceive('get')->once()->andReturn('foo');

        $this->assertEquals('foo', $repo->getAll());
    }

    /** @test */
    public function findBefore()
    {
        list($model, $validator, $repo) = $this->make(__NAMESPACE__.'\RepoWithBefores');
        $query = $this->makeMockQuery();
        $model->shouldReceive('newQuery')->once()->andReturn($query);
        $query->shouldReceive('where')->with(m::any(),'=',10)->once()->andReturn(m::self());
        $query->shouldReceive('doBeforeQueryStuff')->once();
        $query->shouldReceive('first')->once()->andReturn('foo');

        $this->assertEquals('foo', $repo->find(10));
    }

    /** @test */
    public function queryAfter()
    {
        list($model, $validator, $repo) = $this->make(__NAMESPACE__.'\RepoWithAfters');
        $query = $this->makeMockQuery();
        $result = m::mock();
        $model->shouldReceive('newQuery')->once()->andReturn($query);
        $query->shouldReceive('paginate')->once()->andReturn($result);
        $result->shouldReceive('prepareResults')->once();

        $this->assertSame($result, $repo->paginate(20));
    }

    /** @test */
    public function find()
    {
        list($model, $validator, $repo) = $this->make();
        $query = $this->makeMockQuery();
        $model->shouldReceive('newQuery')->once()->andReturn($query);
        $query->shouldReceive('where->first')->once()->andReturn('foo');

        $this->assertEquals('foo', $repo->find(1));
    }

    /** @test */
    public function fetchSinglePrepare()
    {
        list($model, $validator, $repo) = $this->make(__NAMESPACE__.'\RepoWithAfters');
        $query = $this->makeMockQuery();
        $result = m::mock();
        $model->shouldReceive('newQuery->where')->once()->andReturn($query);
        $query->shouldReceive('first')->once()->andReturn($result);
        $result->shouldReceive('prepareResults')->once();

        $this->assertSame($result, $repo->find(1));
    }

    /** @test */
    public function invalidCreate()
    {
        list($model, $validator, $repo) = $this->make(__NAMESPACE__.'\RepoWithBefores');
        $mockModel = $this->makeMockModel();

        $model->shouldReceive('newInstance')->once()->with([])->andReturn($mockModel);
        $validator->shouldReceive('validate')->once()->with('create', [])->andReturn(false);
        $validator->shouldReceive('getErrors')->once()->andReturn([]);

        $this->assertFalse($repo->create([]));
    }

    /** @test */
    public function create()
    {
        list($model, $validator, $repo) = $this->make();
        $mockModel = $this->makeMockModel();

        $model->shouldReceive('newInstance')->once()->with([])->andReturn($mockModel);
        $mockModel->shouldReceive('fill')->once()->with(['foo' => 'bar']);
        $mockModel->shouldReceive('save')->once()->andReturn(true);
        $validator->shouldReceive('validate')->once()->with('create', ['foo' => 'bar'])->andReturn(true);

        $this->assertSame($mockModel, $repo->create(['foo' => 'bar']));
    }

    /** @test */
    public function updateWhenNotExists()
    {
        $this->setExpectedException('RuntimeException');
        list($model, $validator, $repo) = $this->make();
        $updateModel = new RepoTestModelStub;
        $updateModel->exists = false;

        $repo->update($updateModel, []);
    }

    /** @test */
    public function updateValidationFails()
    {
        list($model, $validator, $repo) = $this->make();
        $updateModel = new RepoTestModelStub;
        $updateModel->id = 'foo';
        $updateModel->exists = true;
        $validator->shouldReceive('validate')->once()->with('update', ['id' => 'foo'])->andReturn(false);
        $validator->shouldReceive('getErrors')->once()->andReturn([]);

        $this->assertFalse($repo->update($updateModel, []));
    }

    /** @test */
    public function update()
    {
        list($model, $validator, $repo) = $this->make();
        $updateModel = $this->makeMockModel()->makePartial();
        $updateModel->id = 'foo';
        $updateModel->exists = true;
        $updateModel->shouldReceive('fill')->once()->with(['foo' => 'bar'])->andReturn(m::self());
        $updateModel->shouldReceive('save')->once()->andReturn(true);
        $validator->shouldReceive('validate')->once()->with('update', ['foo' => 'bar', 'id' => 'foo'])->andReturn(true);

        $this->assertTrue($repo->update($updateModel, ['foo' => 'bar']));
    }

    /** @test */
    public function updateOrCreateShouldUpdate()
    {
        list($model, $validator, $repo) = $this->make();

        $mockModel = $this->makeMockModel()->makePartial();
        $mockModel->id = 'foo';
        $mockModel->exists = true;

        $query = $this->makeMockQuery();
        $query->shouldReceive('where')->with('email', '=', 'bar@foo.com')->once()->andReturn(m::self());
        $query->shouldReceive('first')->once()->andReturn($mockModel);

        $model->shouldReceive('newQuery')->once()->andReturn($query);

        $validator->shouldReceive('validate')->once()->with('update', ['id' => 'foo', 'email' => 'bar@foo.com'])->andReturn(false);
        $validator->shouldReceive('getErrors')->once()->andReturn([]);

        $repo->updateOrCreate('email', ['id' => 'foo', 'email' => 'bar@foo.com']);
    }

    /** @test */
    public function updateOrCreateShouldCreate()
    {
        list($model, $validator, $repo) = $this->make();
        $mockModel = $this->makeMockModel();

        $query = $this->makeMockQuery();
        $query->shouldReceive('where')->with('email', '=', 'bar@foo.com')->once()->andReturn(false);
        $query->shouldReceive('first')->once()->andReturn(null);

        $model->shouldReceive('newQuery')->once()->andReturn($query);
        $model->shouldReceive('newInstance')->once()->with([])->andReturn($mockModel);

        $validator->shouldReceive('validate')->once()->with('create', ['id' => 'foo', 'email' => 'bar@foo.com'])->andReturn(false);
        $validator->shouldReceive('getErrors')->once()->andReturn([]);

        $repo->updateOrCreate('email', ['id' => 'foo', 'email' => 'bar@foo.com']);
    }

    /** @test */
    public function delete()
    {
        list($model, $validator, $repo) = $this->make();
        $model = $this->makeMockModel();
        $model->shouldReceive('delete')->once()->andReturn(true);

        $this->assertTrue($repo->delete($model));
    }

    /** @test */
    public function deleteManyIn()
    {
        list($model, $validator, $repo) = $this->make();
        $mockModel = $this->makeMockModel();
        $mockModel->shouldReceive('delete')->once()->andReturn(true);

        $query = $this->makeMockQuery();
        $query->shouldReceive('whereIn')->with('id', array(1,2,3))->once()->andReturn(m::self());
        $query->shouldReceive('get')->once()->andReturn(array($mockModel));

        $model->shouldReceive('newQuery')->once()->andReturn($query);

        $this->assertTrue($repo->deleteManyIn('id', array(1,2,3)));
    }

    protected function make($class = null)
    {
        if (!$class) $class = __NAMESPACE__ . '\RepoStub';

        return [
            $m = $this->makeMockModel(),
            $v = $this->makeMockValidator(),
            $this->makeRepo($m, $v, $class),
        ];
    }

    protected function makeRepo($model, $validator, $class = null)
    {
        if (!$class) $class = __NAMESPACE__ . '\RepoStub';
        return new $class($model, $validator);
    }

    public function makeMockModel($class = null)
    {
        if (!$class) $class = __NAMESPACE__ . '\RepoTestModelStub';

        $mock = m::mock($class);
        $mock->shouldReceive('getQualifiedKeyName')->andReturn('table.id');
        $mock->shouldReceive('getKeyName')->andReturn('id');
        return $mock;
    }

    public function makeMockValidator($class = 'Torann\LaravelRepository\AbstractValidator')
    {
        return m::mock($class);
    }

    public function makeMockQuery()
    {
        return m::mock('Illuminate\Database\Eloquent\Builder');
    }
}

class RepoStub extends \Torann\LaravelRepository\EloquentRepository {}

class RepoWithBefores extends \Torann\LaravelRepository\EloquentRepository
{
    protected function beforeQuery($query, $many)
    {
        $query->doBeforeQueryStuff();
    }

    public function beforeCreate($model, $attributes)
    {
        $model->prepareModel();
    }

    public function beforeUpdate($model, $attributes)
    {
        $model->prepareModel();
    }
}

class RepoWithAfters extends \Torann\LaravelRepository\EloquentRepository
{
    public function afterQuery($results)
    {
        $results->prepareResults();
    }

    public function afterCreate($model)
    {
        $model->prepareModel();
    }

    public function afterUpdate($model)
    {
        $model->prepareModel();
    }
}

class RepoTestModelStub extends \Illuminate\Database\Eloquent\Model
{
}
