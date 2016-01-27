# Laravel Repository

[![Latest Stable Version](https://poser.pugx.org/torann/laravel-repository/v/stable.png)](https://packagist.org/packages/torann/laravel-repository) [![Total Downloads](https://poser.pugx.org/torann/laravel-repository/downloads.png)](https://packagist.org/packages/torann/laravel-repository) [![Build Status](https://travis-ci.org/Torann/laravel-repository.svg?branch=master)](https://travis-ci.org/Torann/laravel-repository)

The Laravel Repository package is meant to be a generic repository implementation for Laravel.

## Table of Contents

- [Installation](#installation])
  - [Composer](#composer)
  - [Laravel](#laravel)
- [Methods](#methods)
  - [RepositoryInterface](#torannlaravelrepositorycontractsrepositoryinterface)
- [Usage](#usage)
  - [Create a Model](#create-a-model)
  - [Create a Repository](#create-a-repository)
  - [Generators](#generators)
  - [Use methods](#use-methods)
  - [Scopes](#scopes)
  - [Using the Scope in a Controller](#using-the-scope-in-a-controller)
- [Cache](#cache)
  - [Usage](#cache-usage)
  - [Config](#cache-config)
- [Authorization](#authorization)
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

``` php
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

### Torann\LaravelRepository\Repositories\RepositoryInterface

- getModel()
- find($id, $columns = ['*'])
- findBy($field, $value, $columns = ['*'])
- findAllBy($attribute, $value, $columns = ['*'])
- findWhere(array $where, $columns = ['*'])
- all($columns = ['*'])
- lists($value, $key = null)
- paginate($limit = null, $columns = ['*'])
- create(array $attributes)
- update(Model $entity, array $attributes)
- delete($entity)
- with(array $relations)
- toSql()
- getErrors()

### Torann\LaravelRepository\RepositoryFactory

- create($name)
- createWithCache($name, array $tags = [])

### Torann\LaravelRepository\Repositories\AbstractCacheDecorator

- getModel()
- find($id, $columns = ['*'])
- findBy($field, $value, $columns = ['*'])
- findAllBy($attribute, $value, $columns = ['*'])
- findWhere(array $where, $columns = ['*'])
- all($columns = ['*'])
- lists($value, $key = null)
- paginate($limit = null, $columns = ['*'])
- create(array $attributes)
- update(Model $entity, array $attributes)
- delete($entity)
- with(array $relations)
- toSql()
- getErrors()
- isSkippedCache()
- getCache($method, array $args = [], Closure $callback, $time = null)

## Basic Setup

The following example will be for a User repository using the default namespace for all repositories `\App\Repositories\`, this can be changed in the configuration file. 

Bellow is the naming scheme for a user repository located `\App\Repositories\Users`:

| Name            | Description                              |
| --------------- | ---------------------------------------- |
| UsersRepository | The main model repository                |
| UsersInterface  | Repository interface                     |
| CacheDecorator  | Cache decorator used for caching repository methods |

As you can see the repository and interface are prefixed with _User_, the name of the repository.

### Create a Model

Create your model normally, but it is important to define the attributes that can be filled from the input form data.

``` php
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

### Create Repository Interface

``` php
<?php

namespace App\Repositories\Users;

use Torann\LaravelRepository\Repositories\RepositoryInterface;

interface UsersInterface extends RepositoryInterface
{
    //
}
```

### Create a Repository

``` php
<?php

namespace App\Repositories\Users;

use Torann\LaravelRepository\Repositories\AbstractRepository;

class UsersRepository extends AbstractRepository implements UsersInterface
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    protected $model = \App\User::class;
}
```

### Register the Repository

Using the provided `RepositoryFactory`, we can quickly instantiate our repository. The factory class uses the namespace provided in the configuration file to find the repository. The reason for the factory class is to help keep the repository service provider slim, especially when we get into cache decorators.

``` php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Torann\LaravelRepository\RepositoryFactory;

class RepositoriesServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Repositories\Users\UsersInterface', function ($app) {
            return RepositoryFactory::create('Users');
        });
    }
}
```

### Use in a Controller

``` php
<?php

namespace App\Http\Controllers;

use App\Repositories\Users\UsersInterface;

class UsersController extends Controller
{
    /**
     * @var UsersInterface
     */
    protected $repository;

    public function __construct(UsersInterface $repository)
    {
        $this->repository = $repository;
    }

    ....
}
```

Find all results in Repository

``` php
$users = $this->repository->all();
```

Find all results in Repository with pagination

``` php
$users = $this->repository->paginate($limit = null, $columns = ['*']);
```

Find by result by id

``` php
$user = $this->repository->find($id);
```

Get a single row by a single column criteria.

``` php
$this->repository->findBy('name', $name);
```

Or you can get all rows by a single column criteria.

``` php
$this->repository->findAllBy('author_id', $author_id);
```

Or you can get all rows by a single column criteria and set of ids.

``` php
$this->repository->findAllBy('author_id', [1, 22, 45]);
```

Get all results by multiple fields

``` php
$this->repository->findWhere([
    'author_id' => $author_id,
    ['year', '>', $year]
]);
```

Create new entry in Repository

``` php
$user = $this->repository->create(Input::all());
```

Update entry in Repository

``` php
$user = $this->repository->find($id);
$user = $this->repository->update($user, $attributes);
```

Delete entry in Repository

``` php
$this->repository->delete($id)
```

Or delete entry in Repository by model object

``` php
$user = $this->repository->find($id);
$this->repository->delete($user)
```

## Scopes

Scopes are a way to change the repository of the query by applying specific conditions according to your needs. You can add multiple Criteria in your repository.

``` php
<?php

namespace App\Repositories\Users;

use Torann\LaravelRepository\Repositories\AbstractRepository;

class UsersRepository extends AbstractRepository implements UsersInterface
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    protected $model = \App\User::class;

    /**
     * Filter by author attribute
     *
     * @return self
     */
    public function scopeAuthorsOnly()
    {
        return $this->addScopeQuery(function($query) {
            return $query->where('is_author', '=', true);
        });
    }
}
```

### Using the Scope in a Controller

``` php
<?php

namespace App\Http\Controllers;

use App\Repositories\Users\UsersInterface;

class UsersController extends Controller
{
    /**
     * @var UsersInterface
     */
    protected $repository;

    /**
     * Create a new Controller instance.
     *
     * @param UsersInterface $repository
     */
    public function __construct(UsersInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of authors.
     *
     * @return Response
     */
    public function index()
    {
        $authors = $this->repository->authorsOnly()->all();

        return \Response::json($authors);
    }
}
```

## Authorization

Out of the box your repositories can support Laravel's build in authorization checks when a **create**, **update**, or **delete** method is performed. You may specify a what is authorized by defining it in the `authorization` property on your repository:

``` php
<?php

namespace App\Repositories\Users;

use Torann\LaravelRepository\Repositories\AbstractRepository;

class UsersRepository extends AbstractRepository implements UsersInterface
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    protected $model = \App\User::class;
  
    /**
     * Array of actions that require authorization.
     *
     * @var array
     */
    protected $authorization = [
        'create',
        'update',
        'destroy',
    ];
}
```

When an authorization fails the method will return `false`. To get the error message bag simple call `getErrors()` on your repository.

## Cache

Add a layer of cache easily to your repository by using the a cache decorator.

#### Cache Usage

> **Note**: Caching uses [Cache Tags](http://laravel.com/docs/5.1/cache#cache-tags), so caching is not supported when using the `file` or `database` cache drivers. This makes the Laravel Repository super scalable.

We will create a cache decorator for our repository `App\Repositories\Users\UsersRepository` from the [Basic Setup](#basic-setup) section. **Note:** you must implement you `UsersInterface` on the decorator.

``` php
<?php

namespace App\Repositories\Users;

use Torann\LaravelRepository\Repositories\AbstractCacheDecorator;

class CacheDecorator extends AbstractCacheDecorator implements UsersInterface
{
    ...
}
```

Before our repository is cached we must update the repository service provider so that it will use the `createWithCache` method:

``` php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Torann\LaravelRepository\RepositoryFactory;

class RepositoriesServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Repositories\Users\UsersInterface', function ($app) {
            return RepositoryFactory::createWithCache('Users');
        });
    }
}
```

Done, your repository will be cached and the repository cache is cleared whenever an item is created, modified or deleted.

``` php
<?php

namespace App\Http\Controllers;

use App\Repositories\Users\UsersInterface;

class UsersController extends Controller
{
    /**
     * @var UsersInterface
     */
    protected $repository;

    public function __construct(UsersInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of users.
     *
     * @return Response
     */
    public function index()
    {
        $users = $this->repository->all();

        return \Response::json($users);
    }
}
```

The repository cache is cleared whenever an item is created, modified or deleted.

#### Cache Config

Enabling and disabling the cache globally can be done in the settings file `config/repositories.php`.

It is possible to override the default settings directly in the repository.

``` php
<?php

namespace App\Repositories\Users;

use Torann\LaravelRepository\Repositories\AbstractCacheDecorator;

class CacheDecorator extends AbstractCacheDecorator implements UsersInterface
{
    /**
     * Lifetime of the cache.
     *
     * @var int
     */
    protected $cacheMinutes = 30;

    ...
}
```

The cacheable methods are: `all`, `lists`, `paginate`, `find`, `findBy`, `findAllBy`, and `findWhere`.

### Caching Custom Methods

This is a quick example showing how to cache a custom repository method called `getRecent`

``` php
<?php

namespace App\Repositories\Users;

use Torann\LaravelRepository\Repositories\AbstractCacheDecorator;

class CacheDecorator extends AbstractCacheDecorator implements UsersInterface
{
    /**
     * Get recent users and cache array
     *
     * @param  int $limit
     *
     * @return null|\Illuminate\Support\Collection
     */
    public function getRecent($limit = 15)
    {
        return $this->getCache('getRecent', func_get_args(), function () use ($limit) {
            return $this->repo->getRecent($limit);
        });
    }
}
```