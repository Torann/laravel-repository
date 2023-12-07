<?php

namespace Torann\LaravelRepository\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Builder;
use Torann\LaravelRepository\Contracts\Scope;

trait Ordering
{
    /**
     * Valid orderable columns.
     */
    protected array $orderable = [];

    /**
     * Default order by column and direction pairs.
     */
    protected array $orderBy = [];

    /**
     * One time skip of ordering. This is done when the
     * ordering is overwritten by the orderBy method.
     */
    protected bool $skipOrderingOnce = false;

    /**
     * {@inheritDoc}
     */
    public function orderBy(mixed $column, string|null $direction): static
    {
        // Ensure the sort is valid
        if (in_array($column, $this->getOrderableKeys()) === false) {
            return $this;
        }

        /** @var Scope|null $scope */
        if ($scope = $this->resolveScope('order_by')) {
            $this->skipOrderingOnce = true;

            $this->addScopeQuery($scope::make(
                Arr::get($this->getOrderable(), $column, $column), $direction
            ));
        }

        return $this;
    }

    /**
     * @param array $order_by
     *
     * @return static
     */
    public function setOrderBy(array $order_by): static
    {
        $this->orderBy = $order_by;

        return $this;
    }

    /**
     * Return the order by array.
     *
     * @return array
     */
    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    /**
     * Set orderable array.
     *
     * @param array|string $values
     * @param bool         $safe
     *
     * @return static
     */
    public function setOrderable(array|string $values, bool $safe = false): static
    {
        if (is_string($values)) {
            $values = [$values => $values];
        }

        foreach ($values as $key => $value) {
            if ($safe === true && isset($this->orderable[$key])) {
                continue;
            }

            $this->orderable[$key] = $value;
        }

        return $this;
    }

    /**
     * Return orderable array.
     *
     * @return array
     */
    public function getOrderable(): array
    {
        return $this->orderable;
    }

    /**
     * Return orderable keys.
     *
     * @return array
     */
    public function getOrderableKeys(): array
    {
        $return = $this->getOrderable();

        return array_values(array_map(function ($value, $key) {
            return (is_array($value) || is_numeric($key) === false) ? $key : $value;
        }, $return, array_keys($return)));
    }

    /**
     * Add aa order by join to the query.
     *
     * @param Builder     $query
     * @param string      $joining_table
     * @param string      $foreign_key
     * @param string      $related_key
     * @param string|null $alias
     *
     * @return string
     */
    public function addOrderByJoin(
        Builder $query,
        string  $joining_table,
        string  $foreign_key,
        string  $related_key,
        string  $alias = null
    ): string {
        // We need to join to the intermediate table
        $local_table = $this->getModel()->getTable();

        // Set the way the table will be joined, with an alias or without
        $table = $alias ? "{$joining_table} as {$alias}" : "{$joining_table}";

        // Create an alias for the join
        $alias = $alias ?: $joining_table;

        // Create the join
        $query->leftJoin($table, "{$alias}.{$foreign_key}", "{$local_table}.{$related_key}");

        return $alias;
    }
}
