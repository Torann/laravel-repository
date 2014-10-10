<?php namespace Torann\LaravelRepository;

use Lang;
use Illuminate\Support\MessageBag;

abstract class AbstractRepository
{
    /**
     * Whether or not to throw exceptions or return null when "find" methods do
     * not yield any results.
     *
     * @var boolean
     */
    protected $throwExceptions = false;

    /**
     * with eager loadings
     *
     * @var array
     */
    protected $with = array();

    /**
     * Whether or not to paginate results.
     *
     * @var boolean
     */
    protected $paginate = false;

    /**
     * The key that should be used when caching the query.
     *
     * @var string
     */
    protected $cacheKey;

    /**
     * The number of minutes to cache the query.
     *
     * @var int
     */
    protected $cacheMinutes;

    /**
     * The Validator instance
     *
     * @var \Illuminate\Validation\Factory
     */
    protected $validator;

    /**
     * The errors MesssageBag instance
     *
     * @var \Illuminate\Support\MessageBag
     */
    protected $errors;

    /**
     * Construct
     *
     * @param mixed $validator
     */
    public function __construct($validator = null)
    {
        $this->validator = $validator;
        $this->errors    = new MessageBag;
    }

    /**
     * Set validator
     *
     * @param mixed $validator
     */
    public function setValidator($validator = null)
    {
        $this->validator = $validator;
    }

    /**
     * Return constant values
     *
     * Get constant values from repository.
     *
     * @return mixed
     */
    public static function get($constant)
    {
        return isset(static::$$constant) ? static::$$constant : null;
    }

    /**
     * Perform an action.
     *
     * Calls $this->before{$action} and $this->after{$action} before or after
     * $this->perform{action} has been called. Also calls $this->valid($action,
     * $attributes) for validation.
     *
     * @param  string  $action
     * @param  mixed   $object
     * @param  mixed   $attributes
     * @param  boolean $validate
     *
     * @return mixed
     */
    protected function perform($action, $object, $attributes = array(), $validate = true)
    {
        $perform = 'perform' . ucfirst($action);
        if (!method_exists($this, $perform)) {
            throw new \BadMethodCallException("Method $perform does not exist on this class");
        }

        // Validate data
        if ($validate === true) {
            if (!$this->valid($action, array_merge($attributes, ['id' => $object->id]))) return false;
        }

        // Before action event
        $beforeResult = $this->doBefore($action, $object, $attributes);
        if ($beforeResult === false) {
            return $beforeResult;
        }

        $result = call_user_func_array([$this, $perform], [$object, $attributes]);
        if ($result === false) {
            return $result;
        }

        // After action event
        $this->doAfter($action, $result, $attributes);

        return $result;
    }

    /**
     * Perform a before or after action.
     *
     * @param  string $which  before or after
     * @param  string $action
     * @param  array  $args
     *
     * @return false|null
     */
    protected function doBeforeOrAfter($which, $action, array $args)
    {
        $method = $which.ucfirst($action);

        if (method_exists($this, $method)) {
            $result = call_user_func_array([$this, $method], $args);
            if ($result === false) return $result;
        }

        return null;
    }

    /**
     * returns the repository itself, for fluent interface
     *
     * @param array $with
     * @return self
     */
    public function with(array $with)
    {
        $this->with = array_merge($this->with, $with);

        return $this;
    }

    /**
     * Indicate that the query results should be cached.
     *
     * @param  \DateTime|int  $minutes
     * @param  string  $key
     * @return $this
     */
    public function remember($minutes, $key = null)
    {
        list($this->cacheMinutes, $this->cacheKey) = array($minutes, $key);

        return $this;
    }

    /**
     * Check to see if the input data is valid
     *
     * @param array $data
     * @return boolean
     */
    public function valid($name, array $data)
    {
        if ($this->validator === null) {
            return true;
        }

        if ($this->validator->validate($name, $data)) {
            return true;
        }

        $this->errors = $this->validator->getErrors();
        return false;
    }

    /**
     * Perform a query.
     *
     * @param  mixed   $query
     * @param  boolean $many
     *
     * @return mixed
     * @throws NotFoundException
     */
    protected function performQuery($query, $many)
    {
        if ($many === false)
        {
            $result = $this->getRegularQueryResults($query, false);

            if (!$result && $this->throwExceptions === true) {
                throw $this->getNotFoundException($query);
            }

            return $result;
        }

        return $this->paginate === false ?
            $this->getRegularQueryResults($query, true) :
            $this->getPaginatedQueryResults($query);
    }

    /**
     * Creates a \Illuminate\Support\MessageBag object, add the error message
     * to it and then set the errors attribute of the user with that bag.
     *
     * @param Model  $user
     * @param string $errorMsg The error message.
     * @param string $key      The key if the error message.
     */
    public function attachErrorMsg($model, $errorMsg, $key, array $values = array())
    {
        $messageBag = $model->errors;

        if (! $messageBag instanceof MessageBag) {
            $messageBag = new MessageBag;
        }

        $messageBag->add($key, Lang::get($errorMsg, $values));
        $this->errors = $messageBag;
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
     * Get a new "not found" exception.
     *
     * @param  mixed $query
     *
     * @return NotFoundException
     */
    protected function getNotFoundException($query)
    {
        return new NotFoundException();
    }

    /**
     * Get regular results from a query builder.
     *
     * @param  mixed   $query
     * @param  boolean $many
     *
     * @return mixed
     */
    protected function getRegularQueryResults($query, $many)
    {
        // Eager Loading
        if(! empty($this->with)) {
            $query->with($this->with);
        }

        // Caching
        if($this->cacheMinutes) {
            dd($this->cacheMinutes);
            $query->remember($this->cacheMinutes, $this->cacheKey);
        }

        return $many ? $query->get() : $query->first();
    }

    /**
     * Get paginated results from a query.
     *
     * @param  mixed $query
     *
     * @return mixed
     */
    protected function getPaginatedQueryResults($query)
    {
        // Caching
        if($this->cacheMinutes) {
            $query->remember($this->cacheMinutes, $this->cacheKey);
        }

        return $query->paginate($this->paginate);
    }

    /**
     * Toggle pagination.
     *
     * @param  false|int $perPage
     *
     * @return object[]
     */
    public function paginate($perPage = 10)
    {
        $this->paginate = $perPage === false ? false : (int) $perPage;

        return $this->getAll();
    }

    /**
     * Do a before action.
     *
     * @see   doBeforeOrAfter
     *
     * @param  string $action
     * @param  object $object
     * @param  mixed  $attributes
     *
     * @return mixed
     */
    protected function doBefore($action, $object, $attributes)
    {
        return $this->doBeforeOrAfter('before', $action, [$object, $attributes]);
    }

    /**
     * Do an after action.
     *
     * @see   doBeforeOrAfter
     *
     * @param  string $action
     * @param  mixed  $result
     * @param  mixed  $attributes
     *
     * @return mixed
     */
    protected function doAfter($action, $result, $attributes)
    {
        return $this->doBeforeOrAfter('after', $action, [$result, $attributes]);
    }

    /**
     * Create and persist a new entity with the given attributes.
     *
     * @param  array  $attributes
     *
     * @return object|false
     */
    public function create(array $attributes)
    {
        return $this->perform('create', $this->getNew(), $attributes, true);
    }

    /**
     * Update an entity with the given attributes and persist it.
     *
     * @param  object  $entity
     * @param  array   $attributes
     *
     * @return boolean
     */
    public function update($entity, array $attributes)
    {
        return $this->perform('update', $entity, $attributes, true) ? true : false;
    }

    /**
     * Update or create new entity.
     *
     * @param  string $key
     * @param  array  $params
     *
     * @return object|false
     */
    public function updateOrCreate($key, $params)
    {
        // Get entity
        $entity = $this->findByAttributes([$key => $params[$key]]);

        // Update entity
        if ($entity) {
            $entity->update($params);
        }
        // Create new entity
        else {
            $entity = $this->create($params);
        }

        return $entity;
    }

    /**
     * Delete an entity.
     *
     * @param  object  $entity
     *
     * @return boolean
     */
    public function delete($entity)
    {
        return $this->perform('delete', $entity, [], false);
    }

    /**
     * Delete a specific row within a range.
     *
     * @param  string $name
     * @param  string $key
     *
     * @return bool
     */
    public function deleteManyIn($name, $key)
    {
        $query = $this->newQuery()
            ->whereIn($name, $key);

        // Get entries
        $entries = $this->fetchMany($query);

        // Delete them!
        foreach ($entries as $entity)
        {
            $this->delete($entity);
        }

        return true;
    }

    /**
     * Perform a query, fetching multiple rows.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *
     * @return mixed
     */
    protected function fetchMany($query)
    {
        return $this->perform('query', $query, true, false);
    }

    /**
     * Perform a query, fetching a single row.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *
     * @return mixed
     */
    protected function fetchSingle($query)
    {
        return $this->perform('query', $query, false, false);
    }

    /**
     * Perform a query, fetching an array of columns.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string $column
     * @param  string $key    Column to be used as the array keys
     *
     * @return array
     */
    protected function fetchList($query, $column = 'id', $key = null)
    {
        $this->doBefore('query', $query, true);

        return $query->lists($column, $key);
    }

    /**
     * Get a specific row by key in the repository.
     *
     * @param  mixed $key
     *
     * @return mixed
     */
    public function find($key)
    {
        $query = $this->newQuery()
            ->where($this->getKeyName(), '=', $key);

        return $this->fetchSingle($query);
    }

    /**
     * Get a specific row by attributes in the repository.
     *
     * @param  array $attributes
     *
     * @return mixed
     * @throws NotFoundException
     */
    public function findByAttributes(array $attributes)
    {
        $query = $this->newAttributesQuery($attributes);

        if (empty($attributes))
        {
            if ($this->throwExceptions)
            {
                throw $this->getNotFoundException($query);
            }

            return null;
        }

        return $this->fetchSingle($query);
    }

    /**
     * Get all the entities for the repository.
     *
     * @return object[]
     */
    public function getAll()
    {
        $query = $this->newQuery();

        return $this->fetchMany($query);
    }

    /**
     * Get a specific row within a range in the repository.
     *
     * @param  string $name
     * @param  string $key
     *
     * @return mixed
     */
    public function getManyIn($name, $key)
    {
        $query = $this->newQuery()
            ->whereIn($name, $key);

        return $this->fetchMany($query);
    }

    /**
     * Get a specific row by attributes in the repository.
     *
     * @param  array $attributes
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException if $attributes is empty
     */
    public function getByAttributes(array $attributes)
    {
        if (empty($attributes)) {
            throw new \InvalidArgumentException('Cannot getByAttributes with an empty set of attributes');
        }

        return $this->fetchMany($this->newAttributesQuery($attributes));
    }

    /**
     * Get a list of columns from the repository.
     *
     * @see    \Illuminate\Database\Query::lists()
     *
     * @param  string $column
     * @param  string $key    Column to be used as the array keys
     *
     * @return array
     */
    public function getList($column = 'id', $key = null)
    {
        return $this->fetchList($this->newQuery(), $column, $key);
    }


    /**
     * Get a new query that searches by attributes.
     *
     * @param  array  $attributes
     * @param  string $operator   Default: '='
     *
     * @return mixed
     */
    protected function newAttributesQuery(array $attributes, $operator = '=')
    {
        $query = $this->newQuery();

        foreach ($attributes as $key => $value) {
            $query->where($key, $operator, $value);
        }

        return $query;
    }

    /**
     * Get a new query builder instance.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected abstract function newQuery();

    /**
     * Get a new entity instance.
     *
     * @param  array  $attributes
     *
     * @return object
     */
    public abstract function getNew(array $attributes = array());

    /**
     * Perform a create action.
     *
     * @param  object  $entity
     * @param  array   $attributes
     *
     * @return object|false
     */
    protected abstract function performCreate($entity, array $attributes);

    /**
     * Perform an update action.
     *
     * @param  object  $entity
     * @param  array   $attributes
     *
     * @return boolean
     */
    protected abstract function performUpdate($entity, array $attributes);

    /**
     * Perform a delete action.
     *
     * @param  object  $entity
     *
     * @return boolean
     */
    protected abstract function performDelete($entity);

    /**
     * Get the name of the primary key to query for.
     *
     * @return string
     */
    protected abstract function getKeyName();

    /**
     * Get the primary key of an entity.
     *
     * @param  object  $entity
     *
     * @return mixed
     */
    protected abstract function getEntityKey($entity);

    /**
     * Get an entity's attributes.
     *
     * @param  object  $entity
     *
     * @return mixed
     */
    protected abstract function getEntityAttributes($entity);
}
