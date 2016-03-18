<?php

namespace Torann\LaravelRepository\Repositories;

use Closure;
use BadMethodCallException;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Torann\LaravelRepository\Exceptions\RepositoryException;

abstract class AbstractRepository implements RepositoryInterface
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $modelInstance;

    /**
     * The errors message bag instance
     *
     * @var \Illuminate\Support\MessageBag
     */
    protected $errors;

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
     * Array of actions that require authorization.
     *
     * Only `create`, `update`, and `destroy` are supported
     *
     * @var array
     */
    protected $authorization = [];

    /**
     * Sortable columns
     *
     * @return array
     */
    protected $sortable = [];

    /**
     * Order by column and direction pair.
     *
     * @var array
     */
    protected $orderBy = [];

    /**
     * Create a new Repository instance
     *
     * @throws RepositoryException
     */
    public function __construct()
    {
        $this->makeModel();
        $this->scopeReset();
        $this->boot();

        // Lumen fix for authorization
        $this->authorization = config('auth') === null ? [] : $this->authorization;
    }

    /**
     * The "booting" method of the repository.
     */
    public function boot()
    {
        //
    }

    /**
     * Return model instance.
     *
     * @return Model
     */
    public function getModel()
    {
        return $this->modelInstance;
    }

    /**
     * Reset internal Query
     *
     * @return $this
     */
    protected function scopeReset()
    {
        $this->scopeQuery = [];

        $this->query = $this->newQuery();

        return $this;
    }

    /**
     * Get a new entity instance
     *
     * @param  array $attributes
     *
     * @return  \Illuminate\Database\Eloquent\Model
     */
    public function getNew(array $attributes = [])
    {
        $this->errors = new MessageBag;

        return $this->modelInstance->newInstance($attributes);
    }

    /**
     * Get a new query builder instance
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQuery()
    {
        $this->query = $this->getNew()->newQuery();

        // Apply order by
        foreach ($this->orderBy as $column => $dir) {
            $this->query->orderBy($column, $dir);
        }

        $this->applyScope();

        return $this;
    }

    /**
     * Find data by id
     *
     * @param  mixed $id
     * @param  array $columns
     *
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
     *
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
     *
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
     * Simple sortable scope.
     *
     * @param array $params
     *
     * @return mixed
     */
    public function scopeSortable(array $params)
    {
        return $this->addScopeQuery(function($query) use ($params) {
            // Get valid sort order
            $order = strtolower(array_get($params, 'order', 'asc'));
            $order = in_array($order, ['desc', 'asc']) ? $order : 'asc';

            // Get sort
            $sort = array_get($params, 'sort', null);

            // Ensure the sort is valid
            if (!in_array($sort, $this->sortable)) {
                return $query;
            }

            // Include the table name
            if (strpos($sort, '.')) {
                $sort = $this->modelInstance->getTable() . '.' . $sort;
            }

            return $query->orderBy($sort, $order);
        });
    }

    /**
     * Retrieve all data of repository
     *
     * @param array $columns
     *
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
     *
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
     *
     * @param null  $limit
     * @param array $columns
     *
     * @return Paginator
     */
    public function paginate($limit = null, $columns = ['*'])
    {
        $this->newQuery();

        $limit = is_null($limit) ? config('repositories.pagination.limit', 15) : $limit;

        return $this->query->paginate($limit, $columns);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $attributes)
    {
        $entity = $this->getNew($attributes);

        // Check authorization
        if ($this->isAuthorized('create', $entity) === false) {
            return false;
        }

        return $entity->save() ? $entity : false;
    }

    /**
     * {@inheritdoc}
     */
    public function update(Model $entity, array $attributes)
    {
        // Check authorization
        if ($this->isAuthorized('update', $entity) === false) {
            return false;
        }

        return $entity->update($attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity)
    {
        if (($entity instanceof Model) === false) {
            $entity = $this->find($entity);
        }

        // Check authorization
        if ($this->isAuthorized('destroy', $entity) === false) {
            return false;
        }

        return $entity->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function with(array $relations)
    {
        $this->with[] = $relations;

        return $this;
    }

    /**
     * Create model instance.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws RepositoryException
     */
    public function makeModel()
    {
        if (!$this->model) {
            throw new RepositoryException("The model class must be set on the repository.");
        }

        return $this->modelInstance = with(new $this->model);
    }

    /**
     * {@inheritdoc}
     */
    public function toSql()
    {
        $this->newQuery();

        return $this->query->toSql();
    }

    /**
     * Return relations array.
     *
     * @return array
     */
    public function getWith()
    {
        return $this->with;
    }

    /**
     * Return query scope.
     *
     * @return array
     */
    public function getScopeQuery()
    {
        return $this->scopeQuery;
    }

    /**
     * Add query scope.
     *
     * @param Closure $scope
     *
     * @return $this
     */
    public function addScopeQuery(Closure $scope)
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

    /**
     * {@inheritdoc}
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorMessage($default = '')
    {
        return $this->errors->first('message') ?: $default;
    }

    /**
     * Check if action is authorized.
     *
     * @param  string $ability
     * @param  Model  $entity
     *
     * @return bool
     */
    public function isAuthorized($ability, $entity)
    {
        if (!in_array($ability, $this->authorization)) {
            return true;
        }

        return $this->authorize($ability, $entity);
    }

    /**
     * Authorize a given action against a set of arguments.
     *
     * @param  string $ability
     * @param  mixed  $arguments
     *
     * @return bool
     */
    public function authorize($ability, $arguments = [])
    {
        try {
            return app(Gate::class)->authorize($ability, $arguments);
        } catch (AuthorizationException $e) {
            $msg = 'This action is unauthorized';

            $this->errors->add('message',
                $e->getMessage() ?: (function_exists('trans') ? trans("errors.$msg") : $msg)
            );

            return false;
        }
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param  string $method
     * @param  array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // Check for scope method and call
        if (method_exists($this, $scope = 'scope' . ucfirst($method))) {
            return call_user_func_array([$this, $scope], $parameters);
        }

        $className = get_class($this);

        throw new BadMethodCallException("Call to undefined method {$className}::{$method}()");
    }
}