<?php

namespace Torann\LaravelRepository\Repositories;

use Closure;
use BadMethodCallException;
use Illuminate\Support\Arr;
use Illuminate\Support\MessageBag;
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
     * Searching operator. This might be different when using
     * a different database driver.
     *
     * @var string
     */
    public static $searchOperator = 'LIKE';

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $modelInstance;

    /**
     * @var \Illuminate\Support\MessageBag
     */
    protected MessageBag $message_bag;

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
     * @throws RepositoryException
     */
    public function __construct()
    {
        $this->makeModel();
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
     * {@inheritDoc}
     */
    public function getModel(): Model
    {
        return $this->modelInstance;
    }

    /**
     * Reset internal Query
     *
     * @return static
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
     * @return Model
     */
    public function getNew(array $attributes = []): Model
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
     * @return static
     */
    public function newQuery(bool $skipOrdering = false)
    {
        $this->query = $this->getNew()->newQuery();

        // Apply order by
        if ($skipOrdering === false && $this->skipOrderingOnce === false) {
            foreach ($this->getOrderBy() as $column => $dir) {
                $this->query->orderBy($column, $dir);
            }
        }

        // Reset the one time skip
        $this->skipOrderingOnce = false;

        $this->applyScope();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function find(mixed $id, array $columns = ['*'])
    {
        $this->newQuery();

        return $this->query->find($id, $columns);
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(string $id, array $columns = ['*'])
    {
        $this->newQuery();

        if ($result = $this->query->find($id, $columns)) {
            return $result;
        }

        // @phpstan-ignore-next-line
        throw (new ModelNotFoundException)->setModel($this->model);
    }

    /**
     * {@inheritDoc}
     */
    public function findBy(string $field, string $value, array $columns = ['*'])
    {
        $this->newQuery();

        return $this->query->where($field, '=', $value)->first($columns);
    }

    /**
     * {@inheritDoc}
     */
    public function findAllBy(string $attribute, mixed $value, array $columns = ['*'])
    {
        $this->newQuery();

        if (is_array($value)) {
            return $this->query->whereIn($attribute, $value)->get($columns);
        }

        return $this->query->where($attribute, '=', $value)->get($columns);
    }

    /**
     * {@inheritDoc}
     */
    public function findWhere(array $where, array $columns = ['*'])
    {
        $this->newQuery();

        foreach ($where as $field => $value) {
            if (is_array($value)) {
                [$field, $condition, $val] = $value;
                $this->query->where($field, $condition, $val);
            } else {
                $this->query->where($field, '=', $value);
            }
        }

        return $this->query->get($columns);
    }

    /**
     * {@inheritDoc}
     */
    public function orderBy(mixed $column, string|null $direction)
    {
        // Ensure the sort is valid
        if (in_array($column, $this->getOrderable()) === false
            && array_key_exists($column, $this->getOrderable()) === false
        ) {
            return $this;
        }

        // One time skip
        $this->skipOrderingOnce = true;

        return $this->addScopeQuery(function ($query) use ($column, $direction) {
            // Get valid sort order
            $direction = in_array(strtolower($direction), ['desc', 'asc']) ? $direction : 'asc';

            // Check for table column mask
            $column = Arr::get($this->getOrderable(), $column, $column);

            return $query->orderBy($this->appendTableName($column), $direction);
        });
    }

    /**
     * @param array $order_by
     *
     * @return static
     */
    public function setOrderBy(array $order_by)
    {
        $this->orderBy = $order_by;

        return $this;
    }

    /**
     * Return the order by array.
     *
     * @return array
     */
    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    /**
     * Set searchable array.
     *
     * @param array|string $key
     * @param mixed        $value
     *
     * @return static
     */
    public function setSearchable(array|string $key, mixed $value = null)
    {
        // Allow for a batch assignment
        if (is_array($key) === false) {
            if ($value === null) {
                $this->searchable[] = $key;

                return $this;
            }

            $key = [$key => $value];
        }

        // Update the searchable values
        foreach ($key as $k => $v) {
            $this->searchable[$k] = $v;
        }

        return $this;
    }

    /**
     * Return searchable keys.
     *
     * @return array
     */
    public function getSearchableKeys(): array
    {
        $return = $this->getSearchable();

        return array_values(array_map(function ($value, $key) {
            return (is_array($value) || is_numeric($key) === false) ? $key : $value;
        }, $return, array_keys($return)));
    }

    /**
     * Return searchable array.
     *
     * @return array
     */
    public function getSearchable(): array
    {
        return $this->searchable;
    }

    /**
     * Return orderable array.
     *
     * @return array
     */
    public function getOrderable(): array
    {
        return $this->orderable;
    }

    /**
     * {@inheritDoc}
     */
    public function search(string|array|null $queries)
    {
        // Adjust for simple search queries
        if (is_string($queries)) {
            $queries = [
                'query' => $queries,
            ];
        }

        return $this->addScopeQuery(function ($query) use ($queries) {
            // Keep track of what tables have been joined and their aliases
            $joined = [];

            foreach ($this->getSearchable() as $param => $columns) {
                // It doesn't always have to map to something
                $param = is_numeric($param) ? $columns : $param;

                // Get param value
                $value = Arr::get($queries, $param, '');

                // Validate value
                if ($value === '' || $value === null) {
                    continue;
                }

                // Columns should be an array
                $columns = (array) $columns;

                // Loop though the columns and look for relationships
                foreach ($columns as $key => $column) {
                    @list($joining_table, $options) = explode(':', $column);

                    if ($options !== null) {
                        @list($column, $foreign_key, $related_key, $alias) = explode(',', $options);

                        // Join the table if it hasn't already been joined
                        if (isset($joined[$joining_table]) == false) {
                            $joined[$joining_table] = $this->addSearchJoin(
                                $query,
                                $joining_table,
                                $foreign_key,
                                $related_key ?: $param, // Allow for related key overriding
                                $alias
                            );
                        }

                        // Set a new column search
                        $columns[$key] = "{$joined[$joining_table]}.{$column}";
                    }
                }

                // Perform a range based query if the range is valid
                // and the separator matches.
                if ($this->createSearchRangeClause($query, $value, $columns)) {
                    continue;
                }

                // Create standard query
                if (count($columns) > 1) {
                    $query->where(function ($q) use ($columns, $param, $value) {
                        foreach ($columns as $column) {
                            $this->createSearchClause($q, $param, $column, $value, 'or');
                        }
                    });
                } else {
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
     * @param mixed $limit
     *
     * @return static
     */
    public function limit(mixed $limit)
    {
        if ($limit = ((int) $limit)) {
            return $this->addScopeQuery(function ($query) use ($limit) {
                return $query->limit($limit);
            });
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function all(array $columns = ['*'])
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
    public function count(array $columns = ['*'])
    {
        $this->newQuery();

        return $this->query->count($columns);
    }

    /**
     * {@inheritDoc}
     */
    public function pluck(string $value, string $key = null)
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
     * @param mixed        $per_page
     * @param array|string $columns
     * @param string       $page_name
     * @param mixed        $page
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(
        mixed        $per_page = null,
        string|array $columns = ['*'],
        string       $page_name = 'page',
        mixed        $page = null
    ) {
        // Get the default per page when not set
        $per_page = ((int) $per_page) ?: config('repositories.per_page', 15);

        // Get the per page max
        $per_page_max = ((int) config('repositories.max_per_page', 100)) ?: 100;

        // Ensure the user can never make the per
        // page limit higher than the defined max.
        if ($per_page > $per_page_max) {
            $per_page = $per_page_max;
        }

        $this->newQuery();

        return $this->query->paginate($per_page, $columns, $page_name, ((int) $page));
    }

    /**
     * Retrieve all data of repository, paginated
     *
     * @param mixed        $per_page
     * @param string|array $columns
     * @param string       $page_name
     * @param mixed        $page
     *
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function simplePaginate(
        mixed        $per_page = null,
        string|array $columns = ['*'],
        string       $page_name = 'page',
        mixed        $page = null
    ) {
        $this->newQuery();

        // Get the default per page when not set
        $per_page = ((int) $per_page) ?: config('repositories.per_page', 15);

        return $this->query->simplePaginate($per_page, $columns, $page_name, ((int) $page));
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function delete(mixed $entity)
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
        if (empty($this->model)) {
            throw new RepositoryException('The model class must be set on the repository.');
        }

        return $this->modelInstance = new $this->model;
    }

    /**
     * Get a new query builder instance with the applied
     * the order by and scopes.
     *
     * @param bool $skipOrdering
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getBuilder(bool $skipOrdering = false)
    {
        $this->newQuery($skipOrdering);

        return $this->query;
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
     * @return static
     */
    public function addScopeQuery(Closure $scope)
    {
        $this->scopeQuery[] = $scope;

        return $this;
    }

    /**
     * Apply scope in current Query
     *
     * @return static
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
     * {@inheritDoc}
     */
    public function getMessageBag(): MessageBag
    {
        if ($this->message_bag === null) {
            $this->message_bag = new MessageBag;
        }

        return $this->message_bag;
    }

    /**
     * {@inheritDoc}
     */
    public function addMessage(string $message, string $key = 'message'): static
    {
        $this->getMessageBag()->add($key, $message);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function hasMessage(string $key = 'message'): bool
    {
        return $this->getMessageBag()->has($key);
    }

    /**
     * {@inheritDoc}
     */
    public function getMessage(string $key = null, string $format = null, string $default = ''): string
    {
        return $this->getMessageBag()->first($key, $format) ?: $default;
    }

    /**
     * {@inheritDoc}
     */
    public function addError(string $message)
    {
        $this->addMessage($message, 'error');

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function hasErrors(): bool
    {
        return $this->getMessageBag()->has('error');
    }

    /**
     * {@inheritDoc}
     */
    public function getErrors(string $format = null): array
    {
        return $this->getMessageBag()->get('error', $format);
    }

    /**
     * {@inheritDoc}
     */
    public function getErrorMessage(string $default = ''): string
    {
        return $this->getMessage('error') ?: $default;
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
        // If missing prepend the table name
        if (strpos($column, '.') === false) {
            return $this->modelInstance->getTable() . '.' . $column;
        }

        // Remove alias prefix indicator
        if (preg_match('/^_\./', $column) != false) {
            return preg_replace('/^_\./', '', $column);
        }

        return $column;
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
        } elseif (is_array($value)) {
            $query->whereIn($this->appendTableName($column), $value, $boolean);
        } else {
            $query->where($this->appendTableName($column), '=', $value, $boolean);
        }
    }

    /**
     * Add a search join to the query.
     *
     * @param Builder $query
     * @param string  $joining_table
     * @param string  $foreign_key
     * @param string  $related_key
     * @param string  $alias
     *
     * @return string
     */
    protected function addSearchJoin(Builder $query, $joining_table, $foreign_key, $related_key, $alias)
    {
        // We need to join to the intermediate table
        $local_table = $this->getModel()->getTable();

        // Set the way the table will be join, with an alias or without
        $table = $alias ? "{$joining_table} as {$alias}" : $joining_table;

        // Create an alias for the join
        $alias = $alias ?: $joining_table;

        // Create the join
        $query->join($table, "{$alias}.{$foreign_key}", "{$local_table}.{$related_key}");

        return $alias;
    }

    /**
     * Add a range clause to the query.
     *
     * @param Builder $query
     * @param string  $value
     * @param array   $columns
     *
     * @return bool
     */
    protected function createSearchRangeClause(Builder $query, $value, array $columns)
    {
        // Skip arrays
        if (is_array($value) === true) {
            return false;
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

            return true;
        }

        return false;
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
