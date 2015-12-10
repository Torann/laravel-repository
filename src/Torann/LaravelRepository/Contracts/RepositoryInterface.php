<?php

namespace Torann\LaravelRepository\Contracts;

use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Torann\LaravelRepository\Exceptions\RepositoryException;

interface RepositoryInterface
{
    /**
     * Reset internal Query
     *
     * @return $this
     */
    public function scopeReset();

    /**
     * Find data by id
     *
     * @param       $id
     * @param array $columns
     * @return Model|Collection
     */
    public function find($id, $columns = ['*']);

    /**
     * Find data by field and value
     *
     * @param       $field
     * @param       $value
     * @param array $columns
     * @return Model|Collection
     */
    public function findBy($field, $value, $columns = ['*']);

    /**
     * Find data by field
     *
     * @param mixed $attribute
     * @param mixed $value
     * @param array $columns
     * @return mixed
     */
    public function findAllBy($attribute, $value, $columns = ['*']);

    /**
     * Find data by multiple fields
     *
     * @param array $where
     * @param array $columns
     * @return mixed
     */
    public function findWhere(array $where, $columns = ['*']);

    /**
     * Retrieve all data of repository
     *
     * @param array $columns
     * @return Collection
     */
    public function all($columns = ['*']);

    /**
     * Get an array with the values of a given column.
     *
     * @param  string $value
     * @param  string $key
     * @return array
     */
    public function lists($value, $key = null);

    /**
     * Retrieve all data of repository, paginated
     * @param null  $limit
     * @param array $columns
     * @return Paginator
     */
    public function paginate($limit = null, $columns = ['*']);

    /**
     * Save a new entity in repository
     *
     * @param array $attributes
     * @return Model
     */
    public function create(array $attributes);

    /**
     * Update an entity with the given attributes and persist it
     *
     * @param  Model $entity
     * @param  array $data
     * @return bool
     */
    public function update(Model $entity, array $data);

    /**
     * Delete a entity in repository by id
     *
     * @param $id
     * @return bool
     */
    public function delete($id);

    /**
     * Load relations
     *
     * @param array $relations
     * @return $this
     */
    public function with(array $relations);

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws RepositoryException
     */
    public function makeModel();

    /**
     * Add query scope.
     *
     * @param \Closure $scope
     * @return $this
     */
    public function addScopeQuery(\Closure $scope);
}