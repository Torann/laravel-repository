<?php

namespace Torann\LaravelRepository\Repositories;

use Closure;
use BadMethodCallException;
use Illuminate\Support\Arr;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Torann\LaravelRepository\Traits\Cacheable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Torann\LaravelRepository\Contracts\RepositoryContract;
use Torann\LaravelRepository\Exceptions\RepositoryException;

abstract class AbstractRepository implements RepositoryContract
{
    use Cacheable;

    /**
     * Cache expires constants
     */
    const EXPIRES_END_OF_DAY = 'eod';

    /**
     * Searching operator.
     *
     * This might be different when using a
     * different database driver.
     *
     * @var string
     */
    public static $searchOperator = 'LIKE';

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
     * Valid orderable columns.
     *
     * @return array
     */
    protected $orderable = [];

    /**
     * Valid searchable columns
     *
     * @return array
     */
    protected $searchable = [];

    /**
     * Default order by column and direction pairs.
     *
     * @var array
     */
    protected $orderBy = [];

    /**
     * One time skip of ordering. This is done when the
     * ordering is overwritten by the orderBy method.
     *
     * @var bool
     */
    protected $skipOrderingOnce = false;

    /**
     * A set of keys used to perform range queries.
     *
     * @var array
     */
    protected $range_keys = [
        'lt', 'gt',
        'bt', 'ne',
    ];

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
     * @param array $attributes
     *
     * @return  \Illuminate\Database\Eloquent\Model
     */
    public function getNew(array $attributes = [])
    {
        $this->errors = new MessageBag;

        return $this->modelInstance->newInstance($attributes);
    }

    /**
     * Get a new query builder instance with the applied
     * the order by and scopes.
     *
     * @param bool $skipOrdering
     *
     * @return self
     */
    public function newQuery($skipOrdering = false)
    {
        $this->query = $this->getNew()->newQuery();

        // Apply order by
        if ($skipOrdering === false && $this->skipOrderingOnce === false) {
            foreach ($this->orderBy as $column => $dir) {
                $this->query->orderBy($column, $dir);
            }
        }

        // Reset the one time skip
        $this->skipOrderingOnce = false;

        $this->applyScope();

        return $this;
    }

    /**
     * Find data by its primary key.
     *
     * @param mixed $id
     * @param array $columns
     *
     * @return Model|Collection
     */
    public function find($id, $columns = ['*'])
    {
        $this->newQuery();

        return $this->query->find($id, $columns);
    }

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param string $id
     * @param  array $columns
     *
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, $columns = ['*'])
    {
        $this->newQuery();

        if ($result = $this->query->find($id, $columns)) {
            return $result;
        }

        throw (new ModelNotFoundException)->setModel($this->model);
    }

    /**
     * Find data by field and value
     *
     * @param string $field
     * @param string $value
     * @param array  $columns
     *
     * @return Model|Collection
     */
    public function findBy($field, $value, $columns = ['*'])
    {
        $this->newQuery();

        return $this->query->where($field, '=', $value)->first($columns);
    }

    /**
     * Find data by field
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $columns
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
     * Order results by.
     *
     * @param string $column
     * @param string $direction
     *
     * @return self
     */
    public function orderBy($column, $direction)
    {
        // Ensure the sort is valid
        if (in_array($column, $this->orderable) === false
            && array_key_exists($column, $this->orderable) === false
        ) {
            return $this;
        }

        // One time skip
        $this->skipOrderingOnce = true;

        return $this->addScopeQuery(function ($query) use ($column, $direction) {

            // Get valid sort order
            $direction = in_array(strtolower($direction), ['desc', 'asc']) ? $direction : 'asc';

            // Check for table column mask
            $column = Arr::get($this->orderable, $column, $column);

            return $query->orderBy($this->appendTableName($column), $direction);
        });
    }

    /**
     * Return searchable keys.
     *
     * @return array
     */
    public function getSearchableKeys()
    {
        return array_values(array_map(function($value, $key) {
            return (is_array($value) || is_numeric($key) === false) ? $key : $value;
        }, $this->searchable, array_keys($this->searchable)));
    }

    /**
     * Filter results by given query params.
     *
     * @param string|array $queries
     *
     * @return self
     */
    public function search($queries)
    {
        // Adjust for simple search queries
        if (is_string($queries)) {
            $queries = [
                'query' => $queries,
            ];
        }

        return $this->addScopeQuery(function ($query) use ($queries) {
            $alias_suffix = 1;

            foreach ($this->searchable as $param => $columns) {
                // It doesn't always have to map to something
                $param = is_numeric($param) ? $columns : $param;

                // Get param value
                $value = Arr::get($queries, $param, '');

                // Validate value
                if ($value === '' || $value === null) continue;

                // Columns should be an array
                $columns = (array)$columns;

                // Loop though the columns and look for relationships
                foreach ($columns as $key=>$column) {
                    @list($joining_table, $options) = explode(':', $column);

                    if ($options !== null) {
                        @list($column, $foreign_key, $related_key) = explode(',', $options);

                        // Allow for overrides
                        $related_key = $related_key ?: $param;

                        // We need to join to the intermediate table
                        $local_table = $this->getModel()->getTable();

                        // Create an alias for the join
                        $alias = "join_{$joining_table}_{$alias_suffix}";

                        // Create the join
                        $query->join(
                            "{$joining_table} as {$alias}",
                            "{$alias}.{$foreign_key}",
                            "{$local_table}.{$related_key}"
                        );

                        // Set a new column search
                        $columns[$key] = "{$alias}.{$column}";

                        $alias_suffix++;
                    }
                }

                // Get the range type
                $range_type = strtolower(substr($value, 0, 2));

                // Perform a range based query if the range is valid
                // and the separator matches.
                if (substr($value, 2, 1) === ':' && in_array($range_type, $this->range_keys)) {
                    // Get the true value
                    $value = substr($value, 3);

                    switch ($range_type) {
                        case 'gt':
                            $query->where($this->appendTableName($columns[0]), '>', $value, 'and');
                            break;
                        case 'lt':
                            $query->where($this->appendTableName($columns[0]), '<', $value, 'and');
                            break;
                        case 'ne':
                            $query->where($this->appendTableName($columns[0]), '<>', $value, 'and');
                            break;
                        case 'bt':
                            // Because this can only have two values
                            if (count($values = explode(',', $value)) === 2) {
                                $query->whereBetween($this->appendTableName($columns[0]), $values);
                            }
                            break;
                    }

                    continue;
                }

                // Create standard query
                if (count($columns) > 1) {
                    $query->where(function ($q) use ($columns, $param, $value) {
                        foreach ($columns as $column) {
                            $this->createSearchClause($q, $param, $column, $value, 'or');
                        }
                    });
                }
                else {
                    $this->createSearchClause($query, $param, $columns[0], $value);
                }
            }

            // Ensure only the current model's table attributes are return
            $query->addSelect([
                $this->getModel()->getTable() . '.*',
            ]);

            return $query;
        });
    }

    /**
     * Set the "limit" value of the query.
     *
     * @param int $limit
     *
     * @return self
     */
    public function limit($limit)
    {
        return $this->addScopeQuery(function ($query) use ($limit) {
            return $query->limit($limit);
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
     * Retrieve the "count" result of the query.
     *
     * @param array $columns
     *
     * @return int
     */
    public function count($columns = ['*'])
    {
        $this->newQuery();

        return $this->query->count($columns);
    }

    /**
     * Get an array with the values of a given column.
     *
     * @param string $value
     * @param string $key
     *
     * @return array
     */
    public function pluck($value, $key = null)
    {
        $this->newQuery();

        $lists = $this->query->pluck($value, $key);

        if (is_array($lists)) {
            return $lists;
        }

        return $lists->all();
    }

    /**
     * Retrieve all data of repository, paginated
     *
     * @param int       $per_page
     * @param array     $columns
     * @param  string   $page_name
     * @param  int|null $page
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($per_page = null, $columns = ['*'], $page_name = 'page', $page = null)
    {
        // Get the per page max
        $per_page_max = config('repositories.max_per_page', 100);

        // Ensure the user can never make the per
        // page limit higher than the defined max.
        if ($per_page > $per_page_max) {
            $per_page = $per_page_max;
        }

        $this->newQuery();

        return $this->query->paginate($per_page, $columns, $page_name, $page);
    }

    /**
     * Retrieve all data of repository, paginated
     *
     * @param  int      $perPage
     * @param  array    $columns
     * @param  string   $pageName
     * @param  int|null $page
     *
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function simplePaginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $this->newQuery();

        return $this->query->simplePaginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Save a new entity in repository
     *
     * @param array $attributes
     *
     * @return Model|bool
     */
    public function create(array $attributes)
    {
        $entity = $this->getNew($attributes);

        if ($entity->save()) {
            $this->flushCache();

            return $entity;
        }

        return false;
    }

    /**
     * Update an entity with the given attributes and persist it
     *
     * @param Model $entity
     * @param array $attributes
     *
     * @return bool
     */
    public function update(Model $entity, array $attributes)
    {
        if ($entity->update($attributes)) {
            $this->flushCache();

            return true;
        }

        return false;
    }

    /**
     * Delete a entity in repository
     *
     * @param mixed $entity
     *
     * @return bool|null
     *
     * @throws \Exception
     */
    public function delete($entity)
    {
        if (($entity instanceof Model) === false) {
            $entity = $this->find($entity);
        }

        if ($entity->delete()) {
            $this->flushCache();

            return true;
        }

        return false;
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
     * Get the raw SQL statements for the request
     *
     * @return string
     */
    public function toSql()
    {
        $this->newQuery();

        return $this->query->toSql();
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
     * Add a message to the repository's error messages.
     *
     * @param string $message
     *
     * @return null
     */
    public function addError($message)
    {
        $this->errors->add('message', $message);

        return null;
    }

    /**
     * Get the repository's error messages.
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get the repository's first error message.
     *
     * @param string $default
     *
     * @return string
     */
    public function getErrorMessage($default = '')
    {
        return $this->errors->first('message') ?: $default;

    }

    /**
     * Append table name to column.
     *
     * @param string $column
     *
     * @return string
     */
    protected function appendTableName($column)
    {
        return (strpos($column, '.') === false)
            ? $this->modelInstance->getTable() . '.' . $column
            : $column;
    }

    /**
     * Add a search where clause to the query.
     *
     * @param Builder $query
     * @param string  $param
     * @param string  $column
     * @param string  $value
     * @param string  $boolean
     */
    protected function createSearchClause(Builder $query, $param, $column, $value, $boolean = 'and')
    {
        if ($param === 'query') {
            $query->where($this->appendTableName($column), self::$searchOperator, '%' . $value . '%', $boolean);
        }
        else {
            $query->where($this->appendTableName($column), '=', $value, $boolean);
        }
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param string $method
     * @param array  $parameters
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
