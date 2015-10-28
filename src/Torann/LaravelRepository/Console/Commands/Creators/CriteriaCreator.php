<?php

namespace Torann\LaravelRepository\Console\Commands\Creators;

class CriteriaCreator extends AbstractCreator
{
    /**
     * @var
     */
    protected $criteria;

    /**
     * @var
     */
    protected $stub_file = 'criteria.stub';

    /**
     * Get the criteria directory.
     *
     * @return string
     */
    protected function getDirectory()
    {
        // Model
        $model = $this->getModel();

        // Get the criteria path from the config file.
        $directory = config('repositories.criteria_path');

        // Check if the model is not null.
        if (isset($model) && ! empty($model)) {
            $directory .= DIRECTORY_SEPARATOR . $this->pluralizeModel();
        }

        // Return the directory.
        return $directory;
    }

    /**
     * Get the populate data.
     *
     * @return array
     */
    protected function getPopulateData()
    {
        // Criteria.
        $criteria = $this->getObjectName();

        // Model
        $model = $this->pluralizeModel();

        // Criteria namespace.
        $criteria_namespace = config('repositories.criteria_namespace');

        // Criteria class.
        $criteria_class = $criteria;

        // Check if the model isset and not empty.
        if (isset($model) && !empty($model)) {
            $criteria_namespace .= '\\' . $model;
        }

        // Populate data.
        $populate_data = [
            'criteria_namespace' => $criteria_namespace,
            'criteria_class' => $criteria_class
        ];

        // Return the populate data.
        return $populate_data;
    }

    /**
     * Get the path.
     *
     * @return string
     */
    protected function getPath()
    {
        return $this->getDirectory() . DIRECTORY_SEPARATOR . $this->getObjectName() . '.php';
    }

    /**
     * Pluralize the model.
     *
     * @return string
     */
    protected function pluralizeModel()
    {
        // Pluralized
        $pluralized = str_plural($this->getModel());

        // Uppercase first character the model name
        $model_name = ucfirst($pluralized);

        // Return the pluralized model.
        return $model_name;
    }
}