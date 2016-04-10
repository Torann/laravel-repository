<?php

namespace Torann\LaravelRepository\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Torann\LaravelRepository\Exceptions\RepositoryException;

interface RepositoryInterface
{
    /**
     * Return model instance.
     *
     * @return Model
     */
    public function getModel();

    /**
     * Find data by id
     *
     * @param  mixed $id
     * @param  array $columns
     *
     * @return Model|Collection
     */
    public function find($id, $columns = ['*']);

    /**
     * Find data by field and value
     *
     * @param       $field
     * @param       $value
     * @param array $columns
     *
     * @return Model|Collection
     */
    public function findBy($field, $value, $columns = ['*']);

    /**
     * Find data by field
     *
     * @param mixed $attribute
     * @param mixed $value
     * @param array $columns
     *
     * @return mixed
     */
    public function findAllBy($attribute, $value, $columns = ['*']);

    /**
     * Find data by multiple fields
     *
     * @param array $where
     * @param array $columns
     *
     * @return mixed
     */
    public function findWhere(array $where, $columns = ['*']);

    /**
     * Retrieve all data of repository
     *
     * @param array $columns
     *
     * @return Collection
     */
    public function all($columns = ['*']);

    /**
     * Get an array with the values of a given column.
     *
     * @param  string $value
     * @param  string $key
     *
     * @return array
     */
    public function lists($value, $key = null);

    /**
     * Retrieve all data of repository, paginated
     *
     * @param null  $limit
     * @param array $columns
     *
     * @return Paginator
     */
    public function paginate($limit = null, $columns = ['*']);

    /**
     * Save a new entity in repository
     *
     * @param array $attributes
     *
     * @return Model|bool
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function create(array $attributes);

    /**
     * Update an entity with the given attributes and persist it
     *
     * @param  Model $entity
     * @param  array $attributes
     *
     * @return bool
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function update(Model $entity, array $attributes);

    /**
     * Delete a entity in repository
     *
     * @param  mixed $entity
     *
     * @return int
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function delete($entity);

    /**
     * Load relations
     *
     * @param array $relations
     *
     * @return $this
     */
    public function with(array $relations);

    /**
     * Get the raw SQL statements for the request
     *
     * @return string
     */
    public function toSql();

    /**
     * Add a message to the repository's error messages.
     *
     * @param  string $message
     *
     * @return null
     */
    public function addError($message);

    /**
     * Get the repository's error messages.
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function getErrors();

    /**
     * Get the repository's first error message.
     *
     * @param string $default
     *
     * @return string
     */
    public function getErrorMessage($default = '');
}