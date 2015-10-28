<?php

namespace Torann\LaravelRepository\Contracts;

use Illuminate\Database\Eloquent\Model;

interface RepositoryInterface
{
    /**
     * @param array $columns
     * @return mixed
     */
    public function all($columns = ['*']);

    /**
     * @param       $perPage
     * @param array $columns
     * @return mixed
     */
    public function paginate($perPage = 1, $columns = ['*']);

    /**
     * Create and persist a new entity with the given attributes
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data);

    /**
     * Update an entity with the given attributes and persist it
     *
     * @param Model $entity
     * @param array $data
     * @return bool
     */
    public function update(Model $entity, array $data);

    /**
     *
     * Delete an entity.
     *
     * @param Model $entity
     * @return bool
     */
    public function delete(Model $entity);

    /**
     * @param       $id
     * @param array $columns
     * @return mixed
     */
    public function find($id, $columns = ['*']);

    /**
     * @param       $field
     * @param       $value
     * @param array $columns
     * @return mixed
     */
    public function findBy($field, $value, $columns = ['*']);

    /**
     * @param       $field
     * @param       $value
     * @param array $columns
     * @return mixed
     */
    public function findAllBy($field, $value, $columns = ['*']);

    /**
     * @param array $where
     * @param array $columns
     * @return mixed
     */
    public function findWhere(array $where, $columns = ['*']);
}