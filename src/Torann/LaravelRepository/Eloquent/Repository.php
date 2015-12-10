<?php

namespace Torann\LaravelRepository\Eloquent;

use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Torann\LaravelRepository\Events\RepositoryEntityEvent;
use Torann\LaravelRepository\Contracts\RepositoryInterface;
use Torann\LaravelRepository\Exceptions\RepositoryException;

abstract class Repository implements RepositoryInterface
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $eloquentModel;

    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $query;

    /**
     * Global query scope.
     *
     * @var array
     */
    protected $scopeQuery = [];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = [];

    /**
     * Create a new Repository instance
     *
     * @throws \Torann\LaravelRepository\Exceptions\RepositoryException
     */
    public function __construct()
    {
        $this->makeModel();
        $this->scopeReset();
        $this->boot();
    }

    /**
     * Reset internal Query
     *
     * @return $this
     */
    public function scopeReset()
    {
        $this->scopeQuery = [];

        $this->query = $this->newQuery();

        return $this;
    }

    /**
     * The "booting" method of the repository.
     */
    public function boot()
    {
        //
    }

    /**
     * Get a new entity instance
     *
     * @param  array $attributes
     * @return  \Illuminate\Database\Eloquent\Model
     */
    public function getNew(array $attributes = [])
    {
        return $this->eloquentModel->newInstance($attributes);
    }

    /**
     * Get a new query builder instance
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function newQuery()
    {
        $this->query = $this->getNew()->newQuery();

//        $this->applyEagerLoads();
        $this->applyScope();

        return $this;
    }

    /**
     * Find data by id
     *
     * @param  mixed $id
     * @param  array $columns
     * @return Model|Collection
     */
    public function find($id, $columns = ['*'])
    {
        $this->newQuery();

        return $this->query->find($id, $columns);
    }

    /**
     * Find data by field and value
     *
     * @param       $field
     * @param       $value
     * @param array $columns
     * @return Model|Collection
     */
    public function findBy($field, $value, $columns = ['*'])
    {
        $this->newQuery();

        return $this->query->where($field, '=', $value)->first();
    }

    /**
     * Find data by field
     *
     * @param mixed $attribute
     * @param mixed $value
     * @param array $columns
     * @return mixed
     */
    public function findAllBy($attribute, $value, $columns = ['*'])
    {
        $this->newQuery();

        // Perform where in
        if (is_array($value)) {
            return $this->query->whereIn($attribute, $value)->get($columns);
        }

        return $this->query->where($attribute, '=', $value)->get($columns);
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
        $this->newQuery();

        foreach ($where as $field => $value) {
            if (is_array($value)) {
                list($field, $condition, $val) = $value;
                $this->query->where($field, $condition, $val);
            }
            else {
                $this->query->where($field, '=', $value);
            }
        }

        return $this->query->get($columns);
    }

    /**
     * Retrieve all data of repository
     *
     * @param array $columns
     * @return Collection
     */
    public function all($columns = ['*'])
    {
        $this->newQuery();

        return $this->query->get($columns);
    }

    /**
     * Get an array with the values of a given column.
     *
     * @param  string $value
     * @param  string $key
     * @return array
     */
    public function lists($value, $key = null)
    {
        $this->newQuery();

        $lists = $this->query->lists($value, $key);

        if (is_array($lists)) {
            return $lists;
        }

        return $lists->all();
    }

    /**
     * Retrieve all data of repository, paginated
     * @param null  $limit
     * @param array $columns
     * @return Paginator
     */
    public function paginate($limit = null, $columns = ['*'])
    {
        $this->newQuery();

        $limit = is_null($limit) ? config('repositories.pagination.limit', 15) : $limit;

        return $this->query->paginate($limit, $columns);
    }

    /**
     * Save a new entity in repository
     *
     * @param array $attributes
     * @return Model
     */
    public function create(array $attributes)
    {
        $model = $this->getNew()->create($attributes);

        if ($model) {
            event(new RepositoryEntityEvent('create', $this));
        }

        return $model;
    }

    /**
     * Update an entity with the given attributes and persist it
     *
     * @param  Model $entity
     * @param  array $attributes
     * @return bool
     */
    public function update(Model $entity, array $attributes)
    {
        $result = $entity->update($attributes);

        if ($result) {
            event(new RepositoryEntityEvent('update', $this));
        }

        return $result;
    }

    /**
     * Delete a entity in repository
     *
     * @param  mixed $entry
     * @return int
     */
    public function delete($entry)
    {
        if (($entry instanceof Model) === false) {
            $entity = $this->find($entry);
        }

        $result = $entity->delete();

        if ($result) {
            event(new RepositoryEntityEvent('delete', $this));
        }

        return $result;
    }

    /**
     * Load relations
     *
     * @param array $relations
     * @return $this
     */
    public function with(array $relations)
    {
        $this->with[] = $relations;

        return $this;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws RepositoryException
     */
    public function makeModel()
    {
        if (!$this->model) {
            throw new RepositoryException("The model class must be set on the repository.");
        }

        return $this->eloquentModel = with(new $this->model);
    }

    /**
     * Query Scope
     *
     * @param \Closure $scope
     * @return $this
     */
    public function scopeQuery(\Closure $scope)
    {
        $this->scopeQuery[] = $scope;

        return $this;
    }

    /**
     * Apply scope in current Query
     *
     * @return $this
     */
    protected function applyScope()
    {
        foreach ($this->scopeQuery as $callback) {
            if (is_callable($callback)) {
                $this->query = $callback($this->query);
            }
        }

        // Clear scopes
        $this->scopeQuery = [];

        return $this;
    }
}