# Laravel Super Repository

[![Latest Stable Version](https://poser.pugx.org/torann/laravel-repository/v/stable.png)](https://packagist.org/packages/torann/laravel-repository) [![Total Downloads](https://poser.pugx.org/torann/laravel-repository/downloads.png)](https://packagist.org/packages/torann/laravel-repository) [![Build Status](https://travis-ci.org/Torann/laravel-promise.svg?branch=master)](https://travis-ci.org/Torann/laravel-promise)


The Laravel Repository package is meant to be a generic repository implementation for Laravel.

----------

## The Super Features

* Support for custom validators
  * Honeypot spam protection
  * Reserved name validation

## Installation

- [Laravel Repository on Packagist](https://packagist.org/packages/torann/laravel-repository)
- [Laravel Repository on GitHub](https://github.com/torann/laravel-repository)

To get the latest version of Laravel Repository simply require it in your `composer.json` file.

```js
"torann/laravel-repository": "0.1.*@dev"
```

You'll then need to run `composer install` to download it and have the autoloader updated.

Once Laravel Repository is installed you need to register the service provider with the application. Open up `app/config/app.php` and find the `providers` key.

Then register the service provider

```php
'Torann\LaravelRepository\ServiceProvider'
```

### Publish configuration file using artisan

```bash
$ php artisan config:publish torann/laravel-repository
```

## Basic usage

The repository classes come with some standard methods for common operations out of the box. First of all some terminology.

Methods starting with "find" is for fetching a single row in the database. Any method calling find will return an object representing a single row or null if it is not found.

Methods starting with "get" is for fetching multiple rows. It will always return an array or array-like object, which may or may not be empty.

### Query methods

- find($key)
- findByAttributes(array $attributes)
- getAll()
- getByAttributes(array $attributes)
- getManyIn($column, array $keys)
- getList($column = 'id', $key = null)

### Persistence methods

- create(array $attributes)
- update(object $entity, array $attributes)
- updateOrCreate($column, $params)
- delete(object $entity)
- deleteManyIn($column, array $keys)

### Other public methods

- getNew(array $attributes) - gets a new entity object
- getErrors()
- paginate($perPage = 15)

### Protected methods

- newQuery() - instantiate a new query builder
- fetchSingle($query) - fetch the first row from a query builder
- fetchMany($query) - fetch all the rows from a query builder
- fetchList($query, $column = 'id', $key = null) - perform a lists() call on a query builder

## Hooks

To make it easy to always apply the same operation to every query ran, the repository has various hooks you can use to modify queries being ran, preparing entities before they're inserted into the database and more. Define these methods on your repository class and they will be invoked automatically.

- beforeQuery($query, boolean $many)
- afterQuery($results)
- beforeCreate($model, array $attributes)
- afterCreate($model, array $attributes)
- beforeUpdate($model, array $attributes)
- afterUpdate($model, array $attributes)

## Validation

While it can be discussed whether validation in a repository is appropriate, often it is very handy, especially in smaller applications.

For each action done by the repository ("create" and "update" out of the box), the method `valid($action, array $attributes)` is called on the validator object. This is made to work with the built in validator.

## Examples

Make sure the repository only ever returns rows related to a specific user.

```php
public function setUser($user)
{
    $this->user = $user;
}

protected function beforeQuery($query, $many)
{
    if (isset($this->user)) {
        $query->where('user_id', '=', $this->user->id);
    }
}
```

Add a custom method that fetches all rows related to a specific user.

```php
public function getForUser($user)
{
    $query = $this->newQuery();
    $query->where('user_id', '=', $user->id);

    return $this->fetchMany($query);
}
```

## Validation

This validation class is a layer on top of Laravel's own Validation class (the one you create by calling Validator::make), meant to be injected into a repository or controller. It allows for more advanced rulesets and more dynamic rules.

## Example

```php
use Torann\LaravelRepository\AbstractValidator;

class MyValidator extends AbstractValidator
{
    protected $rules = [
        'title'  => 'required',
        'status' => 'required'
    ];

    protected $createRules = [
        'user_id' => 'required'
    ];

    protected $updateRules = [
        'id' => 'required|exists:users,id'
    ];
}
```

## Rules

The `$rules` array are common validation rules and are used on every action. They can be overwritten with the action specific rules.

Action events have a suffix of `Rules`.

## Eloquent repositories

To create an Eloquent repository we need to override the constructor and type-hint against our own model and validator and then call the parent's constructor.

```php
use Torann\LaravelRepository\EloquentRepository;

class MyRepository extends EloquentRepository
{
    public function __construct(MyModel $model, MyValidator $validator)
    {
        parent::__construct($model, $validator);
    }
}
```

Two additional hooks are available for eloquent repositories:

- beforeSave($model, array $attributes)
- afterSave($model, array $attributes)

These are called "inside" of the create/update actions. So the order of methods being called is as follows:

1. beforeCreate OR beforeUpdate
2. beforeSave
3. save() is called on the model
4. afterSave
5. afterCreate OR afterUpdate

## Examples

Automatically attach a relationship. `BelongsTo` relationships should be set in beforeCreate/beforeUpdate.

```php
public function beforeCreate($model, array $attributes)
{
    if (isset($this->user)) {
        $model->user()->associate($user);
    }
}
```

All other types in afterCreate/afterUpdate.

```php
public function afterCreate($model, array $attributes)
{
    if (isset($this->user)) {
        $model->users()->attach($user);
    }
}

public function afterSave($model, array $attributes)
{
    if (isset($attributes['related'])) {
        $model->related()->sync($attributes['related']);
    }
}
```

Advanced queries

```php
public function getForUser(User $user)
{
    $query = $this->newQuery()
        ->join('users', 'users.id', '=', 'mytable.user_id')
        ->where('users.id', '=', $user->getKey());

    return $this->fetchMany($query);
}
```