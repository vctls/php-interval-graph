<?php

namespace Vctls\IntervalGraph;

use Closure;

/**
 * Transforms an array of overlapping intervals into another array of adjacent intervals.
 *
 * Each new interval holds the keys of the corresponding original intervals.
 *
 * @package Vctls\IntervalGraph
 */
class Flattener implements FlattenerInterface
{
    /** @var Closure Substract one step from a bound value. */
    protected $substractStep;

    /** @var Closure Add one step to the bound value. */
    protected $addStep;

    /**
     * Set the Closure for decrementing bound values when dealing with
     * discontinuous sets.
     *
     * @param Closure $substractStep
     * @return Flattener
     */
    public function setSubstractStep(Closure $substractStep): FlattenerInterface
    {
        $this->substractStep = $substractStep;
        return $this;
    }

    /**
     * Set the Closure for incrementing bound values when dealing with
     * discontinuous sets.
     *
     * @param Closure $addStep
     * @return Flattener
     */
    public function setAddStep(Closure $addStep): FlattenerInterface
    {
        $this->addStep = $addStep;
        return $this;
    }

    /**
     * Make an array of bounds from an array of intervals.
     *
     * Assign the value of the interval to each bound.
     *
     * Assign and a '+' sign if it is a low bound, and a '-' if it is an high bound.
     * ```
     * bound = [
     *   bound value,
     *   bound type,
     *   TODO included,
     *   interval key,
     *   interval value
     * ]
     * ```
     *
     * @param array[] $intervals
     * @return array[]
     */
    public static function intervalsToSignedBounds(array $intervals): array
    {
        $bounds = [];
        foreach ($intervals as $key => $interval) {
            // TODO Get included boolean from interval bound.
            $bounds[] = [$interval[1], '-', true, $key, $interval[2] ?? null];
            $bounds[] = [$interval[0], '+', true, $key, $interval[2] ?? null];
        }
        // Order the bounds.
        usort($bounds, static function (array $d1, array $d2) {
            return ($d1[0] < $d2[0]) ? -1 : 1;
        });
        return $bounds;
    }

    /**
     * Extract discrete values from an array of intervals.
     *
     * Intervals with the exact same lower and higher bound will be considered as discrete values.
     *
     * They will be removed from the initial array, and returned in a separate array.
     *
     * @param array $intervals The initial array.
     * @return array An array containing only discrete values.
     */
    public static function extractDiscreteValues(array &$intervals): array
    {
        $discreteValues = array_filter($intervals, static function ($interval) {
            return $interval[0] === $interval[1];
        });

        $intervals = array_diff_key($intervals, $discreteValues);

        return $discreteValues;
    }

    /**
     * Create each new interval and calculate its value based on the active intervals on each bound.
     *
     * @param array[] $intervals
     * @return array[]
     */
    public function flatten(array $intervals): array
    {

        $discreteValues = self::extractDiscreteValues($intervals);


        $bounds = self::intervalsToSignedBounds($intervals);
        $newIntervals = [];
        $activeIntervals = [];

        $boundsCount = count($bounds);

        // Create new intervals for each set of two consecutive bounds,
        // and calculate its total value.
        for ($i = 1; $i < $boundsCount; $i++) {

            // Set the current bound.
            [$curBoundValue, $curBoundType, $curBoundIncluded, $curBoundIntervalKey] = $bounds[$i - 1];
            [$nextBoundValue, $nextBoundType, $nextBoundIncluded] = $bounds[$i];

            if ($curBoundType === '+') {
                // If this is a low bound,
                // add the key of the interval to the array of active intervals.
                $activeIntervals[$curBoundIntervalKey] = true;
            } else {
                // If this is an high bound, remove the key.
                unset($activeIntervals[$curBoundIntervalKey]);
            }

            if (
                isset($this->addStep, $this->substractStep) && (
                    ($nextBoundIncluded && $nextBoundType === '+')
                    || (!$nextBoundIncluded && $nextBoundType === '+')
                )
            ) {
                $newHighBound = ($this->substractStep)($nextBoundValue);
            } else {
                $newHighBound = $nextBoundValue;
            }

            if (
                isset($this->addStep, $this->substractStep) && $curBoundType === '-' && $curBoundIncluded
            ) {
                $newLowBound = ($this->addStep)($curBoundValue);
            } else {
                $newLowBound = $curBoundValue;
            }

            $newIntervals[] = [
                $newLowBound,
                $newHighBound,
                $activeIntervals
            ];
        }

        // Remove empty interval generated when two or more intervals share a common bound.
        $newIntervals = array_values(array_filter($newIntervals, static function ($i) {
            // Use weak comparison in case of object typed bounds.
            return $i[0] != $i[1];
        }));


        // Push discrete values back into the array.
        if (!empty($discreteValues)) {
            array_push($newIntervals, ...$discreteValues);
        }

        return $newIntervals;
    }
}
