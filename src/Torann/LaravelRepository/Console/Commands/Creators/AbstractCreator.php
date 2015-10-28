<?php

namespace Torann\LaravelRepository\Console\Commands\Creators;

use Illuminate\Filesystem\Filesystem;

abstract class AbstractCreator
{
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var object
     */
    protected $model;

    /**
     * @var string
     */
    protected $object_name;

    /**
     * @var string
     */
    protected $stub_file;

    /**
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Get the criteria directory.
     *
     * @return string
     */
    abstract protected function getDirectory();

    /**
     * Get the populate data.
     *
     * @return array
     */
    abstract protected function getPopulateData();

    /**
     * Get the path.
     *
     * @return string
     */
    abstract protected function getPath();

    /**
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param mixed $model
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * Get new objects name.
     *
     * @return string
     */
    public function getObjectName()
    {
        return $this->object_name;
    }

    /**
     * Set new objects name.
     *
     * @param string $object_name
     */
    public function setObjectName($object_name)
    {
        $this->object_name = $object_name;
    }

    /**
     * Create new object from stub.
     *
     * @param $name
     * @param $model
     *
     * @return int
     */
    public function create($name, $model)
    {
        // Set the repository.
        $this->setObjectName($name);

        // Set the model.
        $this->setModel($model);

        // Create the directory.
        $this->createDirectory();

        // Return result.
        return $this->createClass();
    }

    /**
     * Create the criteria directory.
     */
    public function createDirectory()
    {
        // Directory
        $directory = $this->getDirectory();

        // Check if the directory exists.
        if (!$this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }
    }

    /**
     * Get the stub.
     *
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function getStub()
    {
        return $this->files->get($this->getStubPath() . $this->stub_file);
    }

    /**
     * Get the stub path.
     *
     * @return string
     */
    protected function getStubPath()
    {
        return __DIR__ . '/../../../../../resources/stubs/';
    }

    /**
     * Populate the stub.
     *
     * @return mixed
     */
    protected function populateStub()
    {
        // Populate data
        $populate_data = $this->getPopulateData();

        // Stub
        $stub = $this->getStub();

        // Loop through the populate data.
        foreach ($populate_data as $key => $value) {
            $stub = str_replace($key, $value, $stub);
        }

        // Return the stub.
        return $stub;
    }

    /**
     * Create the repository class.
     *
     * @return string
     */
    protected function createClass()
    {
        return $this->files->put($this->getPath(), $this->populateStub());
    }
}