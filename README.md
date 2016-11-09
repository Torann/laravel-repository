# Laravel Repository

[![Build Status](https://travis-ci.org/Torann/laravel-repository.svg?branch=master)](https://travis-ci.org/Torann/laravel-repository)
[![Latest Stable Version](https://poser.pugx.org/torann/laravel-repository/v/stable.png)](https://packagist.org/packages/torann/laravel-repository)
[![Total Downloads](https://poser.pugx.org/torann/laravel-repository/downloads.png)](https://packagist.org/packages/torann/laravel-repository)
[![Patreon donate button](https://img.shields.io/badge/patreon-donate-yellow.svg)](https://www.patreon.com/torann)
[![Donate weekly to this project using Gratipay](https://img.shields.io/badge/gratipay-donate-yellow.svg)](https://gratipay.com/~torann)
[![Donate to this project using Flattr](https://img.shields.io/badge/flattr-donate-yellow.svg)](https://flattr.com/profile/torann)
[![Donate to this project using Paypal](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4CJA2A97NPYVU)

The Laravel Repository package is meant to be a generic repository implementation for Laravel.

## Table of Contents

- [Installation](#installation])
  - [Composer](#composer)
  - [Laravel](#laravel)
- [Methods](#methods)
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

> **NOTE** The Service Provider and configuration file is not required for regular use. It is only required when using the [caching](#cache) feature.

### Laravel (optional)

Once installed you need to register the service provider with the application. Open up `config/app.php` and find the `providers` key.

``` php
'providers' => [

    \Torann\LaravelRepository\RepositoryServiceProvider::class,

]
```

### Lumen (optional)

For Lumen register the service provider in `bootstrap/app.php`.

``` php
$app->register(\Torann\LaravelRepository\RepositoryServiceProvider::class);
```

### Publish the configurations (optional)

Run this on the command line from the root of your project:

``` 
$ php artisan vendor:publish --provider="Torann\LaravelRepository\RepositoryServiceProvider" --tag=config
```

A configuration file will be publish to `config/repositories.php`.

## Methods

The following methods are available:

### Torann\LaravelRepository\Contracts\RepositoryContract

- getModel()
- find($id, $columns = ['*'])
- findBy($field, $value, $columns = ['*'])
- findAllBy($attribute, $value, $columns = ['*'])
- findWhere(array $where, $columns = ['*'])
- all($columns = ['*'])
- lists($value, $key = null)
- paginate($limit = null, $columns = ['*'])
- simplePaginate($limit = null, $columns = ['*'])
- create(array $attributes)
- update(Model $entity, array $attributes)
- delete($entity)
- toSql()
- getErrors()
- getErrorMessage($default = '')
- cacheCallback($method, $args, Closure $callback, $time = null)

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

### Create a Repository

``` php
<?php

namespace App\Repositories;

use Torann\LaravelRepository\Repositories\AbstractRepository;

class UsersRepository extends AbstractRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    protected $model = \App\User::class;
}
```

### Use in a Controller

``` php
<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;

class UsersController extends Controller
{
    /**
     * @var UserRepository
     */
    protected $repository;

    public function __construct(UserRepository $repository)
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

namespace App\Repositories;

use Torann\LaravelRepository\Repositories\AbstractRepository;

class UsersRepository extends AbstractRepository
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

use App\Repositories\UserRepository;

class UsersController extends Controller
{
    /**
     * @var UserRepository
     */
    protected $repository;

    /**
     * Create a new Controller instance.
     *
     * @param UserRepository $repository
     */
    public function __construct(UserRepository $repository)
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

## Cache

> **Note**: Caching uses [Cache Tags](http://laravel.com/docs/master/cache#cache-tags), so caching is not supported when using the `file` or `database` cache drivers. This makes the Laravel Repository super scalable.

Caching is meant more for custom repository methods. Standard `find`, `paginate`, `all` and so on do not use caching. The reason for this is that I believe caching should be for more complex queries, such as those that join multiple tables. For such a case we use the following code in our method:

```php
return $this->cacheCallback(__FUNCTION__, func_get_args(), function () use ($id, $columns) {
    return $this->query->find($id, $columns);
});
```

#### Example

This is just a simple example of how caching could work for you.

``` php
<?php

namespace App\Repositories;

use Torann\LaravelRepository\Repositories\AbstractRepository;

class UserRepository extends AbstractRepository
{
    /**
     * Find user by thier email.
     *
     * @param mixed $id
     *
     * @return Model|Collection
     */
    public function findByEmail($email)
    {
        return $this->cacheCallback(__FUNCTION__, func_get_args(), function () use ($email) {
            return $this->query->join('user_emails', 'user_emails.user_id', '=', 'users.id')
                ->where('user_emails.email', $email)
                ->first();
        });
    }
}
```

And that's it! Now we just call

``` php
<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;

class UsersController extends Controller
{
    /**
     * @var UserRepository
     */
    protected $repository;

    public function __construct(UserRepository $repository)
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
        $user = $this->repository->findByEmail('foo@bar.com');

        return \Response::json($user);
    }
}
```

The repository cache is cleared whenever an item is created, modified or deleted.

#### Cache Config

Enabling and disabling the cache globally can be done in the settings file `config/repositories.php`.

It is also possible to override the default settings directly in the repository.

``` php
<?php

namespace App\Repositories;

use Torann\LaravelRepository\Repositories\AbstractRepository;

class UserRepository extends AbstractRepository
{
    /**
     * Lifetime of the cache.
     *
     * @var int
     */
    protected $cacheMinutes = 10;

    ...
}
```