<?php


namespace Vctls\IntervalGraph;


use Closure;

/**
 * Aggregate values passing them directly to the aggregate function.
 *
 * @package Vctls\IntervalGraph
 */
class Aggregator implements AggregatorInterface
{
    /** @var Closure Aggregate interval values. */
    protected $aggregateFunction;

    public function __construct()
    {
        $this->aggregateFunction = static function ($a, $b) {
            if ($a === null && $b === null) {
                return null;
            }
            return round($a + $b, 2);
        };
    }

    /**
     * @return Closure
     */
    public function getAggregateFunction(): Closure
    {
        return $this->aggregateFunction;
    }

    /**
     * Define the function to aggregate interval values.
     *
     * @param Closure $aggregate
     * @return Aggregator
     */
    public function setAggregateFunction(Closure $aggregate): AggregatorInterface
    {
        $this->aggregateFunction = $aggregate;
        return $this;
    }

    /**
     * @param array $originalValues
     * @param $adjacentIntervalValue
     * @return mixed
     */
    protected function getAggregatedValues(array $originalValues, $adjacentIntervalValue)
    {
        return ($this->aggregateFunction)(array_intersect_key($originalValues, $adjacentIntervalValue));
    }

    /**
     * Walk through an array of adjacent intervals, and compute the aggregated values
     * from the values of the corresponding original intervals.
     *
     * @param array $adjacentIntervals
     * @param array $originalIntervals
     * @return array
     */
    public function aggregate(array $adjacentIntervals, array $originalIntervals): array
    {
        $originalValues = [];

        // Get the values of the original intervals, including nulls.
        foreach ($originalIntervals as $i => $interval) {
            $originalValues[$i] = $interval[2] ?? null;
        }

        // If no intervals are active on this bound,
        // the value of this interval is null.
        // Else, aggregate the values of the corresponding intervals.
        foreach ($adjacentIntervals as $key => $adjacentInterval) {
            if (empty($adjacentInterval[2])) {
                $adjacentIntervals[$key][2] = null;
            } else {
                $adjacentIntervals[$key][2] = $this->getAggregatedValues($originalValues, $adjacentInterval[2]);
            }
        }
        return $adjacentIntervals;
    }
}
