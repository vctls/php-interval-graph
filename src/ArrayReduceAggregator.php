<?php


namespace Vctls\IntervalGraph;


use Closure;

/**
 * Aggregate values through `array_reduce`.
 *
 * @package Vctls\IntervalGraph
 */
class ArrayReduceAggregator implements AggregatorInterface
{
    /** @var Closure Aggregate interval values. */
    protected $aggregateFunction;

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
     * @return ArrayReduceAggregator
     */
    public function setAggregateFunction(Closure $aggregate): AggregatorInterface
    {
        $this->aggregateFunction = $aggregate;
        return $this;
    }

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
     * Walk through an array of adjacent intervals, and compute the aggregated values
     * from the values of the corresponding original intervals.
     *
     * @param array $adjacentIntervals
     * @param array $origIntervals
     * @return array
     */
    public function aggregate(array $adjacentIntervals, array $origIntervals): array
    {
        $origIntVals = [];

        // Get the values of the original intervals, including nulls.
        foreach ($origIntervals as $interval) {
            $origIntVals[] = $interval[2] ?? null;
        }

        // If no intervals are active on this bound,
        // the value of this interval is null.
        // Else, aggregate the values of the corresponding intervals.
        foreach ($adjacentIntervals as $key => $adjacentInterval) {
            if (empty($adjacentInterval[2])) {
                $adjacentIntervals[$key][2] = null;
            } else {
                $adjacentIntervals[$key][2] = array_reduce(
                    array_intersect_key($origIntVals, $adjacentInterval[2]),
                    $this->aggregateFunction
                );
            }
        }
        return $adjacentIntervals;
    }
}
