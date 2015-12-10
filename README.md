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
    - [Scopes](#scopes)
    - [Using the Scope in a Controller](#using-the-scope-in-a-controller)
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
 - create(array $attributes)
 - update(Model $entity, array $attributes)
 - delete($entry)
 - find($id, $columns = array('*'))
 - findBy($field, $value, $columns = array('*'))
 - findAllBy($field, $value, $columns = array('*'))
 - findWhere($where, $columns = array('*'))

### Torann\LaravelRepository\Contracts\CacheableInterface

 - getCache($method, $args = null)
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
    protected $model = \App\User::class;
}
```

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

Or you can get all rows by a single column criteria and set of ids.

```php
$this->repository->findAllBy('author_id', [1, 22, 45]);
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
$user = $this->repository->find($id);
$post = $this->repository->update($user, $attributes);
```

Delete entry in Repository

```php
$this->repository->delete($id)
```

Or delete entry in Repository by model object

```php
$user = $this->repository->find($id);
$this->repository->delete($user)
```

### Scopes

Scopes are a way to change the repository of the query by applying specific conditions according to your needs. You can add multiple Criteria in your repository.

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

    /**
     * Create a new Controller instance.
     *
     * @param UsersRepository $repository
     */
    public function __construct(UsersRepository $repository)
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

### Cache

Add a layer of cache easily to your repository

#### Cache Usage

> This is not 100% yet

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

To cache data simple call your method prefixed with `cached`. See example below.

```php
<?php

namespace App\Http\Controllers;

use App\Repositories\PostRepository;

class PostsController extends Controller
{
    /**
     * @var PostRepository
     */
    protected $repository;

    /**
     * Create a new Controller instance.
     *
     * @param UsersRepository $repository
     */
    public function __construct(PostRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a cached listing of posts.
     *
     * @return Response
     */
    public function index()
    {
        $posts = $this->repository->cachedAll();

        return \Response::json($posts);
    }
}
```

The repository cache is cleared whenever an item is created, modified or deleted.

#### Cache Config

You can change the cache settings in the file `config/repositories.php` and also directly on your repository.

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