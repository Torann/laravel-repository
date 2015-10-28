<?php

namespace Torann\LaravelRepository\Eloquent;

use Torann\LaravelRepository\Contracts\CriteriaInterface;
use Torann\LaravelRepository\Criteria\AbstractCriteria;
use Torann\LaravelRepository\Contracts\RepositoryInterface;
use Torann\LaravelRepository\Exceptions\RepositoryException;
use Torann\LaravelRepository\Events\RepositoryEntityEvent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class Repository implements RepositoryInterface, CriteriaInterface
{
    /**
     * Model instance
     *
     * @var
     */
    protected $model;

    /**
     * Criteria to apply.
     *
     * @var Collection
     */
    protected $criteria;

    /**
     * Global query scope.
     *
     * @var \Closure
     */
    protected $scopeQuery = null;

    /**
     * Skip set criteria.
     *
     * @var bool
     */
    protected $skipCriteria = false;

    /**
     * Create a new Repository instance
     *
     * @param \Illuminate\Support\Collection $collection
     * @throws \Torann\LaravelRepository\Exceptions\RepositoryException
     */
    public function __construct(Collection $collection)
    {
        $this->criteria = $collection;

        $this->resetCriteria();
        $this->makeModel();
        $this->boot();
    }

    /**
     * Specify Model class name
     *
     * @return string
     */
    public abstract function model();

    /**
     * The "booting" method of the repository.
     */
    public function boot()
    {
        //
    }

    /**
     * Gets repository model.
     *
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Sets repository model.
     *
     * @param Model $model
     */
    public function setModel(Model $model)
    {
        $this->model = $model;
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function all($columns = ['*'])
    {
        $this->applyScope();
        $this->applyCriteria();

        return $this->model->get($columns);
    }

    /**
     * @param  string $value
     * @param  string $key
     * @return array
     */
    public function lists($value, $key = null)
    {
        $this->applyScope();
        $this->applyCriteria();

        $lists = $this->model->lists($value, $key);

        if (is_array($lists)) {
            return $lists;
        }

        return $lists->all();
    }

    /**
     * @param int   $perPage
     * @param array $columns
     * @return mixed
     */
    public function paginate($perPage = 15, $columns = ['*'])
    {
        $this->applyScope();
        $this->applyCriteria();

        return $this->model->paginate($perPage, $columns);
    }

    /**
     * Create and persist a new entity with the given attributes
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        $model = $this->model->create($data);

        if ($model) {
            event(new RepositoryEntityEvent('create', $this));
        }

        return $model;
    }

    /**
     * Update an entity with the given attributes and persist it
     *
     * @param Model $entity
     * @param array $data
     * @return bool
     */
    public function update(Model $entity, array $data)
    {
        $result = $entity->update($data);

        if ($result) {
            event(new RepositoryEntityEvent('update', $this));
        }

        return $result;
    }

    /**
     *
     * Delete an entity.
     *
     * @param Model $entity
     * @return bool
     */
    public function delete(Model $entity)
    {
        $result = $entity->delete();

        if ($result) {
            event(new RepositoryEntityEvent('delete', $this));
        }

        return $result;
    }

    /**
     * @param       $id
     * @param array $columns
     * @return mixed
     */
    public function find($id, $columns = ['*'])
    {
        $this->applyScope();
        $this->applyCriteria();

        return $this->model->find($id, $columns);
    }

    /**
     * @param       $attribute
     * @param       $value
     * @param array $columns
     * @return mixed
     */
    public function findBy($attribute, $value, $columns = ['*'])
    {
        $this->applyScope();
        $this->applyCriteria();

        return $this->model->where($attribute, '=', $value)->first($columns);
    }

    /**
     * @param       $attribute
     * @param       $value
     * @param array $columns
     * @return mixed
     */
    public function findAllBy($attribute, $value, $columns = ['*'])
    {
        $this->applyScope();
        $this->applyCriteria();

        return $this->model->where($attribute, '=', $value)->get($columns);
    }

    /**
     * Find data by multiple fields
     *
     * @param array $where
     * @param array $columns
     * @return mixed
     */
    public function findWhere(array $where, $columns = ['*'])
    {
        $this->applyScope();
        $this->applyCriteria();

        $model = $this->model;

        foreach ($where as $field => $value) {
            if (is_array($value)) {
                list($field, $condition, $val) = $value;
                $model = $model->where($field, $condition, $val);
            }
            else {
                $model = $model->where($field, '=', $value);
            }
        }

        return $model->get($columns);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws RepositoryException
     */
    public function makeModel()
    {
        $model = app($this->model());

        if (!$model instanceof Model) {
            throw new RepositoryException("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $this->model = $model;
    }

    /**
     * Query Scope
     *
     * @param \Closure $scope
     * @return $this
     */
    public function scopeQuery(\Closure $scope)
    {
        $this->scopeQuery = $scope;

        return $this;
    }

    /**
     * @return $this
     */
    public function resetCriteria()
    {
        $this->skipCriteria(false);

        return $this;
    }

    /**
     * Apply scope in current Query
     *
     * @return $this
     */
    protected function applyScope()
    {
        if (is_callable($this->scopeQuery)) {
            $callback = $this->scopeQuery;
            $this->model = $callback($this->model);
        }

        return $this;
    }

    /**
     * @param AbstractCriteria $criteria
     * @return $this
     */
    public function pushCriteria(AbstractCriteria $criteria)
    {
        $this->criteria->push($criteria);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * @param AbstractCriteria $criteria
     * @return $this
     */
    public function getByCriteria(AbstractCriteria $criteria)
    {
        $this->model = $criteria->apply($this->model, $this);

        return $this;
    }

    /**
     * @param bool $status
     * @return $this
     */
    public function skipCriteria($status = true)
    {
        $this->skipCriteria = $status;

        return $this;
    }

    /**
     * @return $this
     */
    public function applyCriteria()
    {
        if ($this->skipCriteria === true) {
            return $this;
        }

        foreach ($this->getCriteria() as $criteria) {
            if ($criteria instanceof AbstractCriteria) {
                $this->model = $criteria->apply($this->model, $this);
            }
        }

        return $this;
    }
}
