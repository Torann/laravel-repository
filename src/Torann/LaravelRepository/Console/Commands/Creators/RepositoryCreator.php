<?php

namespace Torann\LaravelRepository\Console\Commands\Creators;

class RepositoryCreator extends AbstractCreator
{
    /**
     * @var
     */
    protected $repository;

    /**
     * @var
     */
    protected $stub_file = 'repository.stub';

    /**
     * Get the repository directory.
     *
     * @return mixed
     */
    protected function getDirectory()
    {
        return config('repositories.repository_path');
    }

    /**
     * Get the populate data.
     *
     * @return array
     */
    protected function getPopulateData()
    {
        // Repository namespace.
        $repository_namespace = config('repositories.repository_namespace');

        // Repository class.
        $repository_class = $this->getRepositoryName();

        // Model path.
        $model_path = config('repositories.model_namespace');

        // Model name.
        $model_name = $this->getModelName();

        // Populate data.
        $populate_data = [
            'repository_namespace' => $repository_namespace,
            'repository_class' => $repository_class,
            'model_path' => $model_path,
            'model_name' => $model_name
        ];

        // Return populate data.
        return $populate_data;
    }

    /**
     * Get the path.
     *
     * @return string
     */
    protected function getPath()
    {
        return $this->getDirectory() . DIRECTORY_SEPARATOR . $this->getRepositoryName() . '.php';
    }

    /**
     * Get the repository name.
     *
     * @return mixed|string
     */
    protected function getRepositoryName()
    {
        // Get the repository.
        $repository_name = studly_case($this->getObjectName());

        // Check if the repository ends with 'Repository'.
        if (!strpos($repository_name, 'Repository') !== false) {
            $repository_name .= 'Repository';
        }

        // Return repository name.
        return $repository_name;
    }

    /**
     * Get the model name.
     *
     * @return string
     */
    protected function getModelName()
    {
        // Set model.
        $model = $this->getModel();

        // Check if the model isset.
        if (isset($model) && ! empty($model)) {
            $model_name = $model;
        }
        else {
            // Set the model name by the stripped repository name.
            $model_name = str_singular($this->stripRepositoryName());
        }

        // Return the model name.
        return $model_name;
    }

    /**
     * Get the stripped repository name.
     *
     * @return string
     */
    protected function stripRepositoryName()
    {
        // Lowercase the repository.
        $repository = strtolower($this->getObjectName());

        // Remove repository from the string.
        $stripped = str_replace('repository', '', $repository);

        // Uppercase repository name.
        $result = ucfirst($stripped);

        // Return the result.
        return $result;
    }
}