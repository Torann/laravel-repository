<?php

namespace Torann\LaravelRepository\Contracts;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @implements \ArrayAccess<TKey, TValue>
 */
interface RepositoryContract
{
    /**
     * Return model instance.
     *
     * @return Model
     */
    public function getModel(): Model;

    /**
     * Find data by id
     *
     * @param mixed $id
     * @param array $columns
     *
     * @return Model|Collection
     */
    public function find(mixed $id, array $columns = ['*']);

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param string $id
     * @param array  $columns
     *
     * @return Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(string $id, array $columns = ['*']);

    /**
     * Find data by field and value
     *
     * @param string $field
     * @param string $value
     * @param array  $columns
     *
     * @return Model|object|static|null
     */
    public function findBy(string $field, string $value, array $columns = ['*']);

    /**
     * Find data by field
     *
     * @param mixed $attribute
     * @param mixed $value
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function findAllBy(string $attribute, mixed $value, array $columns = ['*']);

    /**
     * Find data by multiple fields
     *
     * @param array $where
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function findWhere(array $where, array $columns = ['*']);

    /**
     * Order results by.
     *
     * @param mixed       $column
     * @param string|null $direction
     *
     * @return static
     */
    public function orderBy(mixed $column, string|null $direction);

    /**
     * Filter results by given query params.
     *
     * @param string|array $queries
     *
     * @return static
     */
    public function search(string|array $queries);

    /**
     * Retrieve all data of repository
     *
     * @param array $columns
     *
     * @return Collection
     */
    public function all(array $columns = ['*']);

    /**
     * Get an array with the values of a given column.
     *
     * @param string      $value
     * @param string|null $key
     *
     * @return array<TKey, TValue>
     */
    public function pluck(string $value, string $key = null);

    /**
     * Retrieve all data of repository, paginated
     *
     * @param mixed        $per_page
     * @param string|array $columns
     *
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function paginate(mixed $per_page = null, string|array $columns = ['*']);

    /**
     * Retrieve all data of repository, paginated
     *
     * @param mixed        $per_page
     * @param string|array $columns
     *
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function simplePaginate(mixed $per_page = null, string|array $columns = ['*']);

    /**
     * Save a new entity in repository
     *
     * @param array $attributes
     *
     * @return Model|bool
     */
    public function create(array $attributes);

    /**
     * Update an entity with the given attributes and persist it
     *
     * @param Model $entity
     * @param array $attributes
     *
     * @return bool
     */
    public function update(Model $entity, array $attributes);

    /**
     * Delete a entity in repository
     *
     * @param mixed $entity
     *
     * @return bool|null
     * @throws \Exception
     */
    public function delete(mixed $entity);

    /**
     * Get the raw SQL statements for the request
     *
     * @return string
     */
    public function toSql();

    /**
     * Add a message to the repository's error messages.
     *
     * @param string $message
     * @param string $key
     *
     * @return static
     */
    public function addError(string $message, string $key = 'message');

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
    public function getErrorMessage(string $default = ''): string;
}
