<?php

namespace Torann\Tests;

use Torann\LaravelRepository\Contracts\CriteriaInterface as Criteria;
use Torann\LaravelRepository\Contracts\RepositoryInterface as Repository;
use Torann\LaravelRepository\Eloquent\EloquentRepository;
use Illuminate\Database\Eloquent\Model;
use \Mockery as m;
use \PHPUnit_Framework_TestCase as TestCase;

class RepositoryTest extends TestCase {

    protected $mock;

    protected $repository;

    public function setUp() {
        $this->mock = m::mock('Illuminate\Database\Eloquent\Model');
    }
}

