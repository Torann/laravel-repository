# Laravel Repository

[![Latest Stable Version](https://poser.pugx.org/torann/laravel-repository/v/stable.png)](https://packagist.org/packages/torann/laravel-repository) [![Total Downloads](https://poser.pugx.org/torann/laravel-repository/downloads.png)](https://packagist.org/packages/torann/laravel-repository) [![Build Status](https://travis-ci.org/Torann/laravel-repository.svg?branch=master)](https://travis-ci.org/Torann/laravel-repository)

The Laravel Repository package is meant to be a generic repository implementation for Laravel.

## Table of Contents

- [Installation](#installation])
    - [Composer](#composer)
    - [Laravel](#laravel)
- [Methods](#methods)
    - [RepositoryInterface](#torannlaravelrepositorycontractsrepositoryinterface)
    - [CriteriaInterface](#torannlaravelrepositorycontractscriteriainterface)
    - [CacheableInterface](#torannlaravelrepositorycontractscacheableinterface)
- [Usage](#usage)
    - [Create a Model](#create-a-model)
    - [Create a Repository](#create-a-repository)
    - [Generators](#generators)
    - [Use methods](#use-methods)
    - [Create a Criteria](#create-a-criteria)
    - [Using the Criteria in a Controller](#using-the-criteria-in-a-controller)
- [Cache](#cache)
    - [Usage](#cache-usage)
    - [Config](#cache-config)
- [Laravel Repository on Packagist](https://packagist.org/packages/torann/laravel-repository)
- [Laravel Repository on GitHub](https://github.com/torann/laravel-repository)

## Installation

### Composer

From the command line run:

```
$ composer require torann/laravel-repository
```

### Laravel

Once installed you need to register the service provider with the application. Open up `config/app.php` and find the `providers` key.

```php
'providers' => [

    \Torann\LaravelRepository\RepositoryProvider::class,

]
```

### Publish the configurations

Run this on the command line from the root of your project:

```
$ php artisan vendor:publish --provider="Torann\LaravelRepository\RepositoryProvider"
```

A configuration file will be publish to `config/repositories.php`.

## Methods

The following methods are available:

### Torann\LaravelRepository\Contracts\RepositoryInterface

 - all($columns = array('*'))
 - lists($value, $key = null)
 - paginate($perPage = 1, $columns = array('*'));
 - create(array $data)
 - update(array $data, $id, $attribute = "id")
 - delete($id)
 - find($id, $columns = array('*'))
 - findBy($field, $value, $columns = array('*'))
 - findAllBy($field, $value, $columns = array('*'))
 - findWhere($where, $columns = array('*'))

### Torann\LaravelRepository\Contracts\CriteriaInterface

 - apply($model, Repository $repository)

### Torann\LaravelRepository\Contracts\CacheableInterface

 - getCache($method, $args = null, Closure $callback)
 - getCacheKey($method, $args = null)
 - getCacheMinutes()
 - skipCache($status = true)

## Usage

### Create a Model

Create your model normally, but it is important to define the attributes that can be filled from the input form data.

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = [
        'title',
        'username',
        ...
     ];

     ...
}
```

### Create a Repository

```php
<?php

namespace App\Repositories;

use Torann\LaravelRepository\Eloquent\Repository;
use Torann\LaravelRepository\Contracts\RepositoryInterface;

class UsersRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return 'App\User';
    }
}
```

### Generators

Create your repositories easily through the generator.

#### Config

You must first configure the storage location of the repository files. By default is the "app" folder and the namespace "App".

##### Command `make:repository`

To generate a repository for your User model, use the following command

```
php artisan make:repository User
```

Use a different model from the repository

```
php artisan make:repository User --model=SystemUser
```

> This command will create the new repository inside the repository folder set in the config file (default "App/Repositories").

##### Command `make:criteria`

To generate criteria use the following command

```
php artisan make:criteria SystemAdmin
```

Use a different model from the repository

```
php artisan make:criteria SystemAdmin --model=SystemUser
```

> This command will create the new criteria inside the criteria folder set in the config file (default "App/Repositories/Criteria").

### Use methods

```php
<?php

namespace App\Http\Controllers;

use App\Repositories\UsersRepository;

class UsersController extends Controller
{
    /**
     * @var PostRepository
     */
    protected $repository;

    public function __construct(UsersRepository $repository)
    {
        $this->repository = $repository;
    }

    ....
}
```

Find all results in Repository

```php
$posts = $this->repository->all();
```

Find all results in Repository with pagination

```php
$posts = $this->repository->paginate($limit = null, $columns = ['*']);
```

Find by result by id

```php
$post = $this->repository->find($id);
```

Get a single row by a single column criteria.

```php
$this->repository->findBy('title', $title);
```

Or you can get all rows by a single column criteria.

```php
$this->repository->findAllBy('author_id', $author_id);
```

Get all results by multiple fields

```php
$this->repository->findWhere([
    'author_id' => $author_id,
    ['year', '>', $year]
]);
```

Create new entry in Repository

```php
$post = $this->repository->create(Input::all());
```

Update entry in Repository

```php
$post = $this->repository->update(Input::all(), $id);
```

Delete entry in Repository

```php
$this->repository->delete($id)
```

### Create a Criteria

Criteria are a way to change the repository of the query by applying specific conditions according to your needs. You can add multiple Criteria in your repository.

```php
<?php

namespace App\Repositories\Criteria\Users;

use Torann\LaravelRepository\Criteria\AbstractCriteria;
use Torann\LaravelRepository\Contracts\RepositoryInterface;

class MyCriteria extends AbstractCriteria
{
    /**
     * @param $model
     * @param RepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository)
    {
        $model = $model->where('is_admin', true);

        return $model;
    }
}
```

### Using the Criteria in a Controller

```php
<?php

namespace App\Http\Controllers;

use App\Repositories\UsersRepository;
use App\Repositories\Criteria\Users\MyCriteria;

class UsersController extends Controller
{
    /**
     * @var PostRepository
     */
    protected $repository;

    public function __construct(UsersRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index()
    {
        $this->film->pushCriteria(new MyCriteria());

        return \Response::json($this->film->all());
    }
}
```

Setting the default Criteria in Repository

```php
use Torann\LaravelRepository\Eloquent\Repository;

class PostRepository extends Repository
{
    public function boot()
    {
        $this->pushCriteria(new MyCriteria());
        $this->pushCriteria(new AnotherCriteria());
        ...
    }

    function model()
    {
       return "App\\Post";
    }
}
```

### Skip criteria defined in the repository

Use `skipCriteria` before any other chaining method

```php
$posts = $this->repository->skipCriteria()->all();
```

#### Cache Usage

> **Note**: Caching uses [Cache Tags](http://laravel.com/docs/5.1/cache#cache-tags), so caching is not supported when using the `file` or `database` cache drivers.

Implements the interface CacheableInterface and use CacheableRepository Trait.

```php
use Torann\LaravelRepository\Eloquent\Repository;
use Torann\LaravelRepository\Traits\CacheableRepository;
use Torann\LaravelRepository\Contracts\CacheableInterface;

class PostRepository extends Repository implements CacheableInterface
{
    use CacheableRepository;

    ...
}
```

Done, your repository will be cached and the repository cache is cleared whenever an item is created, modified or deleted.

#### Cache Config

You can change the cache settings in the file *config/repositories.php* and also directly on your repository.

It is possible to override these settings directly in the repository.

```php
use Torann\LaravelRepository\Eloquent\Repository;
use Torann\LaravelRepository\Contracts\CacheableInterface;
use Torann\LaravelRepository\Traits\CacheableRepository;

class PostRepository extends Repository implements CacheableInterface
{
    use CacheableRepository;

    /**
     * Lifetime of the cache.
     *
     * @var int
     */
    protected $cacheMinutes = 90;

    /**
     * Method to include in caching.
     *
     * @var array
     */
    protected $cacheOnly = ['all', ...];

    // OR

    /**
     * Method to exclude from caching.
     *
     * @var array
     */
    protected $cacheExcept = ['find', ...];

    ...
}
```

The cacheable methods are: `all`, `lists`, `paginate`, `find`, `findBy`, `findAllBy`, and `findWhere`.