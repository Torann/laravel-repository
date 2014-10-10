<?php namespace Torann\LaravelRepository;

use Illuminate\Database\Eloquent\Model;

abstract class EloquentRepository extends AbstractRepository
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * Whether to call push() or just save() when creating/updating a model.
     *
     * @var boolean
     */
    protected $push = false;

    /**
     * Construct
     *
     * @param \Torann\LaravelRepository\AbstractValidator $validator
     */
    public function __construct(Model $model, $validator = null)
    {
        parent::__construct($validator);

        $this->model = $model;
    }

    /**
     * {@inheritdoc}
     *
     * @return  \Illuminate\Database\Eloquent\Model
     */
    public function getNew(array $attributes = array())
    {
        return $this->model->newInstance($attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function update($model, array $attributes)
    {
        if (!$model->exists) {
            throw new \RuntimeException('Cannot update non-existant model');
        }

        return parent::update($model, $attributes);
    }

    /**
     * {@inheritdoc}
     *
     * @return  \Illuminate\Database\Eloquent\Model
     */
    protected function performCreate($model, array $attributes)
    {
        $model->fill($attributes);

        return $this->perform('save', $model, $attributes, false);
    }

    /**
     * {@inheritdoc}
     */
    protected function performUpdate($model, array $attributes)
    {
        $model->fill($attributes);

        return $this->perform('save', $model, $attributes, false);
    }

    /**
     * {@inheritdoc}
     */
    protected function performSave($model, array $attributes)
    {
        $method = $this->push ? 'push' : 'save';

        return $model->$method() ? $model : false;
    }

    /**
     * {@inheritdoc}
     */
    protected function performDelete($model)
    {
        return $model->delete();
    }

    /**
     * {@inheritdoc}
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function newQuery()
    {
        return $this->model->newQuery();
    }

    /**
     * {@inheritdoc}
     */
    protected function getKeyName()
    {
        return $this->model->getQualifiedKeyName();
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityKey($model)
    {
        return $model->getKey();
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityAttributes($model)
    {
        return $model->getAttributes();
    }

    /**
     * returns the model found by search criteria
     *
     * @param string $search
     * @param array $columns
     * @return Model
     */
    public function applySearch($query, $search, $columns = array())
    {
        if ($search)
        {
            $query->where(function($query) use ($columns, $search)
            {
                $value = '%'.str_replace(' ', '%', $search).'%';

                foreach ($columns as $column)
                {
                    $query->orWhere($column, 'like', $value);
                }
            });
        }
    }
}
