<?php

namespace Torann\LaravelRepository\Contracts;

use Torann\LaravelRepository\Criteria\AbstractCriteria;

interface CriteriaInterface
{
    /**
     * @param bool $status
     * @return $this
     */
    public function skipCriteria($status = true);

    /**
     * @return mixed
     */
    public function getCriteria();

    /**
     * @param AbstractCriteria $criteria
     * @return $this
     */
    public function getByCriteria(AbstractCriteria $criteria);

    /**
     * @param AbstractCriteria $criteria
     * @return $this
     */
    public function pushCriteria(AbstractCriteria $criteria);

    /**
     * @return $this
     */
    public function applyCriteria();
}