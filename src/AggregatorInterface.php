<?php

namespace Vctls\IntervalGraph;

use Closure;

interface AggregatorInterface
{

    /**
     * @return Closure
     */
    public function getAggregateFunction(): Closure;

    /**
     * Define the function to aggregate interval values.
     *
     * @param Closure $aggregate
     * @return AggregatorInterface
     */
    public function setAggregateFunction(Closure $aggregate): AggregatorInterface;

    /**
     * Walk through an array of adjacent intervals, and compute the aggregated values
     * from the values of the corresponding original intervals.
     *
     * @param array $adjacentIntervals
     * @param array $origIntervals
     * @return array
     */
    public function aggregate(array $adjacentIntervals, array $origIntervals): array;
}
