<?php


namespace Vctls\IntervalGraph;


/**
 * Aggregate values through `array_reduce`.
 *
 * @package Vctls\IntervalGraph
 */
class ArrayReduceAggregator extends Aggregator
{
    /**
     * @param array $originalValues
     * @param $adjacentIntervalValue
     * @return mixed
     */
    protected function getAggregatedValues(array $originalValues, $adjacentIntervalValue)
    {
        return array_reduce(array_intersect_key($originalValues, $adjacentIntervalValue), $this->aggregateFunction);
    }
}
