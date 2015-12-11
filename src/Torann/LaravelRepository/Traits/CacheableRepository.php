<?php

namespace Torann\LaravelRepository\Traits;

trait CacheableRepository
{
    /**
     * Retrieve all data of repository
     *
     * @param array $columns
     * @return mixed
     */
    public function all($columns = ['*'])
    {
        return $this->getCache('all', func_get_args(), function () use ($columns) {
            return parent::all($columns);
        });
    }

    /**
     * @param  string $value
     * @param  string $key
     * @return array
     */
    public function lists($value, $key = null)
    {
        return $this->getCache('lists', func_get_args(), function () use ($value, $key) {
            return parent::lists($value, $key);
        });
    }

    /**
     * Retrieve all data of repository, paginated
     * @param null  $limit
     * @param array $columns
     * @return mixed
     */
    public function paginate($limit = null, $columns = ['*'])
    {
        return $this->getCache('paginate', func_get_args(), function () use ($limit, $columns) {
            return parent::paginate($limit, $columns);
        });
    }

    /**
     * Find data by id
     *
     * @param       $id
     * @param array $columns
     * @return mixed
     */
    public function find($id, $columns = ['*'])
    {
        return $this->getCache('find', func_get_args(), function () use ($id, $columns) {
            return parent::find($id, $columns);
        });
    }

    /**
     * @param       $attribute
     * @param       $value
     * @param array $columns
     * @return mixed
     */
    public function findBy($attribute, $value, $columns = ['*'])
    {
        return $this->getCache('findBy', func_get_args(), function () use ($attribute, $value, $columns) {
            return parent::findBy($attribute, $value, $columns);
        });
    }

    /**
     * @param       $attribute
     * @param       $value
     * @param array $columns
     * @return mixed
     */
    public function findAllBy($attribute, $value, $columns = ['*'])
    {
        return $this->getCache('findAllBy', func_get_args(), function () use ($attribute, $value, $columns) {
            return parent::findAllBy($attribute, $value, $columns);
        });
    }

    /**
     * Find data by multiple fields
     *
     * @param array $where
     * @param array $columns
     * @param bool  $or
     * @return mixed
     */
    public function findWhere(array $where, $columns = ['*'], $or = false)
    {
        return $this->getCache('findWhere', func_get_args(), function () use ($where, $columns, $or) {
            return parent::findWhere($where, $columns, $or);
        });
    }
}