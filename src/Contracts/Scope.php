<?php

namespace Torann\LaravelRepository\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface Scope
{
    /**
     * @param array $arguments
     *
     * @return static
     */
    public static function make(...$arguments): static;

    /**
     * Apply the scope
     *
     * @param Builder    $builder
     * @param Repository $repository
     *
     * @return mixed
     */
    public function apply(Builder $builder, Repository $repository): Builder;
}
